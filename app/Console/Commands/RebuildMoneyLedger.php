<?php

namespace App\Console\Commands;

use App\Services\Money\LedgerSync;
use App\Services\Money\MoneyRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Writes the whole money ledger again from the payments and refunds, under the rules that are in force now.
 *
 * It exists for the day the money rules change (the ledger is never edited, so old lines keep the old rules). It is for a
 * system that only holds demo money: it throws away the ledger and EVERY payout statement, which is why a PAID statement
 * (money really sent to a vendor) stops it unless `--force-demo` says that the money was never real.
 */
class RebuildMoneyLedger extends Command
{
    protected $signature = 'money:rebuild-ledger {--force-demo : also delete PAID or reversed payout statements (they were demo money)}';

    protected $description = 'Throw away the money ledger and all payout statements and book every payment and refund again under the current rules (demo data only)';

    public function handle(LedgerSync $sync): int
    {
        $paid = DB::table('payout_statements')->whereIn('status', ['paid', 'reversed'])->count();

        if ($paid > 0 && !$this->option('force-demo')) {
            $this->error("There are {$paid} payout statement(s) marked paid or reversed: that is money sent to vendors. Nothing was changed.");
            $this->line('If that money was only demo money, run again with --force-demo.');

            return self::FAILURE;
        }

        $before = [
            'ledger lines'      => DB::table('ledger_entries')->count(),
            'payout statements' => DB::table('payout_statements')->count(),
            'statement lines'   => DB::table('payout_statement_lines')->count(),
        ];

        // statements and their lines first (they point at ledger lines); the query builder is used on purpose: the models refuse deletes
        DB::transaction(function () {
            DB::table('payout_statement_lines')->delete();
            DB::table('payout_events')->delete();
            DB::table('payout_statements')->delete();
            DB::table('ledger_entries')->delete();
        });

        MoneyRules::forget();
        $added = ['payments' => 0, 'refunds' => 0];
        do {
            $run = $sync->run();
            $added['payments'] += $run['payments'];
            $added['refunds']  += $run['refunds'];
        } while ($run['payments'] + $run['refunds'] > 0);

        $this->table(['', 'before', 'after'], [
            ['ledger lines', $before['ledger lines'], DB::table('ledger_entries')->count()],
            ['payout statements', $before['payout statements'], DB::table('payout_statements')->count()],
            ['statement lines', $before['statement lines'], DB::table('payout_statement_lines')->count()],
        ]);
        $this->info("Booked again: {$added['payments']} payment line(s), {$added['refunds']} refund line(s).");

        // the new lines must follow the rule: the platform keeps only its commission
        $off = DB::table('ledger_entries')->whereRaw('(platform_service_paisa + commission_paisa + vendor_paisa) <> gross_paisa')->count();
        $off += DB::table('ledger_entries')->whereRaw('(base_paisa + service_paisa + vat_paisa) <> gross_paisa')->count();

        if ($off > 0) {
            $this->error("{$off} ledger line(s) do not add up. Look at them before you pay anyone.");

            return self::FAILURE;
        }

        $this->info('Every ledger line adds up.');

        return self::SUCCESS;
    }
}
