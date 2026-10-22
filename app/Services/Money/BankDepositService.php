<?php

namespace App\Services\Money;

use App\Models\BankDeposit;
use App\Models\LedgerEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The bank side of the reconciliation. SSLCommerz pays what guests paid into the admin's bank in batches, less its fee.
 * Every few days (the "bank check" setting) the admin writes down what arrived; this works out what SHOULD have arrived
 * for the same payments and shows the difference.
 *
 * What should arrive for a period =  guests' payments in the period  −  refunds paid out in the period  −  SSLCommerz's fees on those payments.
 * (Payments made through the local test shortcut never reached SSLCommerz and are left out.)
 * Numbers come from the money ledger and are whole paisa.
 */
class BankDepositService
{
    public function __construct(private readonly MoneyRules $rules) {}

    /** How many days between bank checks (7, 15, 30, or any number the admin chose) */
    public function checkDays(): int
    {
        return max(1, min(90, (int) $this->rules->get('bank_check_days')));
    }

    /**
     * What should have arrived for the payments made from $from to $to (Dhaka days, both included).
     *
     * @return array{payments:int,refunds:int,fees:int,expected:int,payment_count:int,fee_unknown_count:int}
     */
    public function expected(string $from, string $to): array
    {
        $row = LedgerEntry::query()->where('is_test', false)->whereBetween('occurred_on', [$from, $to])
            ->selectRaw("
                coalesce(sum(case when type = 'payment' then gross_paisa else 0 end), 0) as payments,
                coalesce(-sum(case when type = 'refund' then gross_paisa else 0 end), 0) as refunds,
                coalesce(sum(case when type = 'payment' then gateway_fee_paisa else 0 end), 0) as fees,
                coalesce(sum(case when type = 'payment' then 1 else 0 end), 0) as payment_count,
                coalesce(sum(case when type = 'payment' and gateway_fee_paisa is null then 1 else 0 end), 0) as fee_unknown_count
            ")->first();

        $out = ['payments' => (int) $row->payments, 'refunds' => (int) $row->refunds, 'fees' => (int) $row->fees,
            'payment_count' => (int) $row->payment_count, 'fee_unknown_count' => (int) $row->fee_unknown_count];
        $out['expected'] = $out['payments'] - $out['refunds'] - $out['fees'];

        return $out;
    }

    /** The last deposit that is still in force */
    public function last(): ?BankDeposit
    {
        return BankDeposit::whereNull('voided_at')->orderByDesc('period_to')->orderByDesc('id')->first();
    }

    /**
     * The next stretch to check: it starts the day after the last deposit's period ends (or, before any deposit, the day the first
     * REAL payment came in: test payments never reach the bank) and is as long as the "bank check" setting, never going into today.
     * It is only "overdue" when that stretch holds real money to confirm: with nothing real in it there is nothing to check.
     *
     * @return array{from:string,to:string,due_on:string,overdue:bool,today:string}
     */
    public function next(): array
    {
        $today = CarbonImmutable::now(MoneyRules::TZ)->startOfDay();
        $from  = $this->last() ? CarbonImmutable::parse($this->last()->period_to->toDateString(), MoneyRules::TZ)->addDay() : $this->firstDay();
        $to    = $from->addDays($this->checkDays() - 1);
        $shown = $to->gte($today) ? $today->subDay() : $to;

        $hasMoney = false;
        if ($shown->gte($from)) {
            $e = $this->expected($from->toDateString(), $shown->toDateString());
            $hasMoney = $e['payment_count'] > 0 || $e['refunds'] > 0;
        }

        return [
            'from' => $from->toDateString(), 'to' => $shown->toDateString(),
            'due_on' => $to->addDay()->toDateString(), 'overdue' => $today->gt($to) && $hasMoney, 'today' => $today->toDateString(),
        ];
    }

    /** Where the first bank check starts: the first real payment (never before the books began) */
    private function firstDay(): CarbonImmutable
    {
        $start = $this->rules->startsAt()->startOfDay();
        $first = LedgerEntry::query()->where('is_test', false)->min('occurred_on');

        if (!$first) {
            return $start;
        }

        $day = CarbonImmutable::parse(substr((string) $first, 0, 10), MoneyRules::TZ)->startOfDay();

        return $day->gt($start) ? $day : $start;
    }

    /** Payments recorded after the last deposit's period: not yet confirmed in the bank (can be a long way from "missing") */
    public function awaitingBank(): int
    {
        $last = $this->last();
        $from = $last ? $last->period_to->copy()->addDay()->toDateString() : $this->rules->startsAt()->toDateString();

        return max(0, $this->expected($from, '9999-12-31')['expected']);
    }

    /**
     * Write down a deposit.
     *
     * @param array{period_from:string,period_to:string,received_on:string,received_taka:string|int|float,reference?:?string,note?:?string} $d
     */
    public function record(User $by, array $d): BankDeposit
    {
        $from  = CarbonImmutable::parse($d['period_from'], MoneyRules::TZ)->startOfDay();
        $to    = CarbonImmutable::parse($d['period_to'], MoneyRules::TZ)->startOfDay();
        $today = CarbonImmutable::now(MoneyRules::TZ)->startOfDay();

        if ($to->lt($from)) {
            $this->fail('period_to', 'The end of the period is before its start.');
        }
        if ($to->gte($today)) {
            $this->fail('period_to', 'The period must end before today: today\'s payments are not complete yet.');
        }
        if ($from->diffInDays($to) > 120) {
            $this->fail('period_to', 'That period is too long. Enter it in parts of up to 120 days.');
        }

        $receivedOn = CarbonImmutable::parse($d['received_on'], MoneyRules::TZ)->startOfDay();

        if ($receivedOn->gt($today)) {
            $this->fail('received_on', 'The day the money reached the bank cannot be in the future.');
        }
        if ($receivedOn->lt($from)) {
            $this->fail('received_on', 'The money cannot have reached the bank before the first day of the period.');
        }

        $received = $this->paisa($d['received_taka']);

        return DB::transaction(function () use ($by, $d, $from, $to, $received) {
            // Everyone who writes a deposit takes the same one row first, so they go one after the other. (Locking only the overlapping
            // rows is not enough: with nothing to lock yet, two writers at once block each other's insert and one gets a deadlock error.)
            DB::table('settings')->where('key', 'bank_check_days')->lockForUpdate()->first();

            // two deposits cannot cover the same day
            $overlap = BankDeposit::whereNull('voided_at')->where('period_from', '<=', $to->toDateString())->where('period_to', '>=', $from->toDateString())->first();

            if ($overlap) {
                $this->fail('period_from', 'These days are already covered by the deposit of ' . $overlap->period_from->toDateString() . ' to ' . $overlap->period_to->toDateString() . '. Void that one first to enter it again.');
            }

            $e = $this->expected($from->toDateString(), $to->toDateString());

            return BankDeposit::create([
                'period_from' => $from->toDateString(), 'period_to' => $to->toDateString(), 'received_on' => CarbonImmutable::parse($d['received_on'], MoneyRules::TZ)->toDateString(),
                'received_paisa' => $received, 'expected_paisa' => $e['expected'], 'difference_paisa' => $received - $e['expected'], 'fee_unknown_count' => $e['fee_unknown_count'],
                'reference' => ($r = trim((string) ($d['reference'] ?? ''))) !== '' ? $r : null, 'note' => ($n = trim((string) ($d['note'] ?? ''))) !== '' ? $n : null,
                'entered_by' => $by->id, 'entered_by_name' => $by->name,
            ]);
        }, 3);
    }

    public function void(BankDeposit $deposit, User $by, string $reason): BankDeposit
    {
        if (mb_strlen(trim($reason)) < 5) {
            $this->fail('reason', 'Write why this deposit is being voided (at least a few words).');
        }

        return DB::transaction(function () use ($deposit, $by, $reason) {
            $locked = BankDeposit::lockForUpdate()->findOrFail($deposit->id);

            if ($locked->isVoided()) {
                $this->fail('deposit', 'This deposit is already voided.');
            }

            $locked->update(['voided_at' => now(), 'voided_by' => $by->id, 'voided_by_name' => $by->name, 'void_reason' => trim($reason)]);

            return $locked->fresh();
        });
    }

    /** "1,23,456.50" / "123456.5" / 123456.5 -> paisa, or a refusal */
    private function paisa(mixed $taka): int
    {
        $text = str_replace([',', ' '], '', (string) $taka);

        if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $text)) {
            $this->fail('received_taka', 'Write the amount that reached the bank in taka, like 120000 or 120000.50.');
        }

        return MoneySplitter::toPaisa($text);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
