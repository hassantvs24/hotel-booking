<?php

namespace App\Services\Money;

use App\Models\LedgerEntry;
use App\Models\RefundRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * An independent second count of the money, made straight from the payment and refund records and compared with
 * the ledger. It does not use the ledger's own arithmetic. If the two ever disagree, something is wrong and the
 * admin is told, instead of a wrong number sitting quietly in a report.
 *
 * Three questions:
 *   1. Does the money in the ledger equal the payments the bank confirmed?
 *   2. Does the money taken back equal the refunds that completed?
 *   3. Is every line's commission what the rate on that line gives (worked out again here with whole-number maths)?
 */
class MoneyRecount
{
    public function __construct(private readonly MoneyRules $rules) {}

    /**
     * @return array{ok:bool,payments_raw:int,payments_ledger:int,refunds_raw:int,refunds_ledger:int,bad_commission:int,differences:array<int,array<string,mixed>>}
     */
    public function run(int $limit = 20): array
    {
        // Payments the ledger has written down: compare each with the bank-confirmed payment record behind it.
        $payments = DB::table('ledger_entries as l')
            ->join('transactions as t', 't.id', '=', 'l.transaction_id')
            ->where('l.type', LedgerEntry::TYPE_PAYMENT)
            ->whereIn('t.status', ['completed', 'refunded'])
            ->selectRaw('l.booking_number, l.gross_paisa as ledger, round(t.amount * 100) as raw');

        // Refunds that completed against a payment the ledger holds
        $refunds = DB::table('ledger_entries as l')
            ->join('refund_requests as r', 'r.id', '=', 'l.refund_request_id')
            ->where('l.type', LedgerEntry::TYPE_REFUND)
            ->where('r.status', RefundRequest::STATUS_COMPLETED)
            ->selectRaw('l.booking_number, -l.gross_paisa as ledger, round(coalesce(r.approved_amount, 0) * 100) as raw');

        $differences = [];
        foreach ([['payment', $payments], ['refund', $refunds]] as [$kind, $query]) {
            foreach ($query->get() as $row) {
                if ((int) $row->ledger !== (int) $row->raw) {
                    $differences[] = ['kind' => $kind, 'booking_number' => $row->booking_number, 'ledger_paisa' => (int) $row->ledger, 'bank_records_paisa' => (int) $row->raw];
                }
            }
        }

        // Money in the records that is NOT in the ledger and should be: that is "waiting", reported elsewhere, but a refund
        // that completed against a ledgered payment and has no ledger line is a real gap.
        $missingRefunds = RefundRequest::query()
            ->where('status', RefundRequest::STATUS_COMPLETED)
            ->whereExists(fn ($q) => $q->from('ledger_entries')->select(DB::raw(1))->where('ledger_entries.type', LedgerEntry::TYPE_PAYMENT)->whereColumn('ledger_entries.transaction_id', 'refund_requests.transaction_id'))
            ->whereNotExists(fn ($q) => $q->from('ledger_entries')->select(DB::raw(1))->where('ledger_entries.type', LedgerEntry::TYPE_REFUND)->whereColumn('ledger_entries.refund_request_id', 'refund_requests.id'))
            ->count();

        // Commission worked out again with whole numbers only: basis points, half a paisa rounds up.
        // (done by the database: integer maths only, the same on every line)
        $badCommission = LedgerEntry::query()
            ->where('type', LedgerEntry::TYPE_PAYMENT)
            ->whereNotNull('commission_percent')
            ->whereRaw('((base_paisa * round(commission_percent * 100) + 5000) div 10000) <> commission_paisa')
            ->count();

        $ledgerIn  = (int) LedgerEntry::where('type', LedgerEntry::TYPE_PAYMENT)->sum('gross_paisa');
        $ledgerOut = -1 * (int) LedgerEntry::where('type', LedgerEntry::TYPE_REFUND)->sum('gross_paisa');

        return [
            'ok'              => !$differences && !$missingRefunds && !$badCommission,
            'payments_raw'    => (int) DB::table('ledger_entries as l')->join('transactions as t', 't.id', '=', 'l.transaction_id')->where('l.type', LedgerEntry::TYPE_PAYMENT)->whereIn('t.status', ['completed', 'refunded'])->sum(DB::raw('round(t.amount * 100)')),
            'payments_ledger' => $ledgerIn,
            'refunds_raw'     => (int) DB::table('ledger_entries as l')->join('refund_requests as r', 'r.id', '=', 'l.refund_request_id')->where('l.type', LedgerEntry::TYPE_REFUND)->where('r.status', RefundRequest::STATUS_COMPLETED)->sum(DB::raw('round(coalesce(r.approved_amount, 0) * 100)')),
            'refunds_ledger'  => $ledgerOut,
            'missing_refunds' => $missingRefunds,
            'bad_commission'  => $badCommission,
            'differences'     => array_slice($differences, 0, $limit),
        ];
    }
}
