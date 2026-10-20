<?php

namespace App\Services\Money;

use App\Models\GatewayCheck;
use App\Models\RefundRequest;
use App\Services\Refund\SecretScrubber;
use App\Services\Refund\SslCommerzPaymentLookup;
use App\Services\Refund\SslCommerzRefundService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asks SSLCommerz about our payments and says where its answer and our records differ.
 *
 * It only reports. It never changes a booking, a payment or the money ledger.
 *
 * What it looks at, each by the id SSLCommerz knows (a booking number, or a cart reference):
 *  - every real payment we have recorded: is it there, for the same amount, and is the fee what we recorded?
 *  - every booking that was NOT confirmed here (waiting, failed, expired): did the guest pay anyway? (money taken, no booking)
 *  - every refund we completed with SSLCommerz: does SSLCommerz agree it was done?
 * Payments made through the local test shortcut never reached SSLCommerz and are left out on purpose.
 */
class GatewayChecker
{
    /** how long, in days, a payment is worth asking about again and again */
    private const KEEP_CHECKING_DAYS = 60;
    /** an agreeing payment is asked about again after this many days (a late refund or chargeback shows up) */
    private const RECHECK_OK_DAYS = 7;
    /** a booking that was never confirmed is watched for this many days */
    private const UNCONFIRMED_DAYS = 14;
    /**
     * A booking still waiting is left alone for this long before it is asked about: the guest may be paying right now, and the site's own
     * pick-up (every 5 minutes) and the 30-minute expiry both ask SSLCommerz themselves. After this, "paid but not confirmed" is a real finding.
     */
    private const UNCONFIRMED_GRACE_MINUTES = 40;
    /** SSLCommerz could not be reached this many times in a row: now it is a problem to look at */
    private const UNREACHABLE_LIMIT = 6;

    public function __construct(private readonly SslCommerzPaymentLookup $gateway, private readonly MoneyRules $rules) {}

    /**
     * Ask about as many payments as the budget allows, most urgent first.
     * $recheckOpen: also ask again about every problem still open, not only the ones not asked for a day (the "Check now" button).
     * $auto: true only for the run the server's scheduler starts by itself (that is what shows the scheduler is alive).
     *
     * @return array{checked:int,agreed:int,problems:int,errors:int}
     */
    public function run(int $maxAsks = 60, float $seconds = 45.0, bool $recheckOpen = false, bool $auto = false): array
    {
        $out  = ['checked' => 0, 'agreed' => 0, 'problems' => 0, 'errors' => 0];
        $lock = Cache::lock('gateway-check-run', (int) ceil($seconds) + 60);

        // the automatic run and the "Check now" button must not ask about the same payments at the same time
        if (!$lock->get()) {
            return $out;
        }

        try {
            return $this->runLocked($maxAsks, $seconds, $out, $recheckOpen, $auto);
        } finally {
            $lock->release();
        }
    }

    /** @param array{checked:int,agreed:int,problems:int,errors:int} $out */
    private function runLocked(int $maxAsks, float $seconds, array $out, bool $recheckOpen, bool $auto): array
    {
        DB::table('gateway_check_runs')->where('started_at', '<', now()->subDays(30))->delete();

        $runId = DB::table('gateway_check_runs')->insertGetId(['started_at' => now(), 'auto' => $auto]);
        $stop  = microtime(true) + $seconds;

        foreach ($this->due($maxAsks, $recheckOpen) as $subject) {
            if (microtime(true) > $stop) {
                break;
            }

            try {
                $row = $subject['kind'] === 'refund' ? $this->checkRefund($subject) : $this->checkPayment($subject);
            } catch (Throwable $e) {
                Log::warning('[Reconcile] Could not check ' . $subject['tran_id'] . ': ' . SecretScrubber::describe($e));
                $out['errors']++;

                // an answer that cannot be read is a failed try like any other: counted, and shown after many in a row (never silently dropped)
                try {
                    $this->unreachable($subject, true);
                } catch (Throwable $again) {
                    Log::warning('[Reconcile] Could not record the failure for ' . $subject['tran_id'] . ': ' . SecretScrubber::describe($again));
                }

                continue;
            }

            $out['checked']++;
            $row->result === GatewayCheck::OK ? $out['agreed']++ : ($row->result === 'gateway_error' ? $out['errors']++ : $out['problems']++);
        }

        DB::table('gateway_check_runs')->where('id', $runId)->update($out + ['finished_at' => now()]);

        return $out;
    }

    /** Ask about one id right now (also used after a fix, and by tests) */
    public function checkOne(string $tranId): ?GatewayCheck
    {
        foreach ([$this->paymentSubjects(), $this->unconfirmedSubjects(), $this->refundSubjects()] as $list) {
            foreach ($list as $subject) {
                if ($subject['tran_id'] === $tranId) {
                    return $subject['kind'] === 'refund' ? $this->checkRefund($subject) : $this->checkPayment($subject);
                }
            }
        }

        return null;
    }

    // ────────────────────────────────────────────────────────  what to ask about

    /** @return \Generator<int,array<string,mixed>> the subjects that are due, most urgent first */
    private function due(int $limit, bool $recheckOpen = false): \Generator
    {
        $known = GatewayCheck::query()->get()->keyBy('tran_id');
        $now   = now();
        $all   = array_merge($this->paymentSubjects(), $this->unconfirmedSubjects(), $this->refundSubjects());
        $due   = [];

        foreach ($all as $s) {
            $c = $known[$s['tran_id']] ?? null;

            // 0 never asked or our side changed since, 1 a problem still open (asked daily), 2 could not be reached (asked again soon),
            // 3 agreed (or looked at) but old
            $quiet = $c && ($c->result === GatewayCheck::OK || $c->resolved_at !== null);

            if (!$c || $c->our_status !== $s['our_status'] || (int) $c->our_paisa !== $s['paisa']) {
                $due[] = [0, $s];
            } elseif ($c->attempts > 0 && ($c->result === 'gateway_error' ? $c->checked_at : $c->updated_at)->lt($now->copy()->subMinutes(30))) {   // (a kept verdict keeps its checked_at, so the last failed try is its updated_at)
                $due[] = [2, $s];
            } elseif (!$quiet && ($recheckOpen || $c->checked_at->lt($now->copy()->subHours(20)))) {
                $due[] = [1, $s];
            } elseif ($quiet && $this->recheckable($s) && $c->checked_at->lt($now->copy()->subDays($s['kind'] === 'unconfirmed' ? 1 : self::RECHECK_OK_DAYS))) {
                $due[] = [3, $s];
            }
        }

        usort($due, fn ($a, $b) => $a[0] <=> $b[0] ?: strcmp((string) ($a[1]['at'] ?? ''), (string) ($b[1]['at'] ?? '')));

        foreach (array_slice($due, 0, $limit) as [, $s]) {
            yield $s;
        }
    }

    private function recheckable(array $s): bool
    {
        $limit = $s['kind'] === 'unconfirmed' ? self::UNCONFIRMED_DAYS : self::KEEP_CHECKING_DAYS;

        return $s['at'] && CarbonImmutable::parse($s['at'], 'UTC')->gt(now()->subDays($limit));
    }

    /**
     * Real payments we recorded since the books started, one subject per booking or per cart.
     *
     * @return array<int,array<string,mixed>>
     */
    private function paymentSubjects(): array
    {
        $from = $this->rules->startsAt()->utc();

        $rows = DB::select("
            select coalesce(bg.group_ref, cast(t.booking_id as char)) as tran_id,
                   sum(round(t.amount * 100)) as paisa,
                   sum(t.status = 'completed') as completed,
                   max(t.updated_at) as at,
                   group_concat(distinct t.booking_id order by t.booking_id) as bookings
            from transactions t
            left join bookings b on b.booking_number = t.booking_id
            left join booking_groups bg on bg.id = b.booking_group_id
            where t.status in ('completed', 'refunded')
              and t.deleted_at is null
              and (t.transaction_reference is null or t.transaction_reference not like 'LOCAL-TEST%')
              and t.updated_at >= ?
            group by coalesce(bg.group_ref, cast(t.booking_id as char))
        ", [$from]);

        return array_map(fn ($r) => [
            'kind' => 'payment', 'tran_id' => (string) $r->tran_id, 'our_status' => (int) $r->completed > 0 ? 'completed' : 'refunded',
            'paisa' => (int) $r->paisa, 'at' => $r->at, 'bookings' => array_map('intval', explode(',', (string) $r->bookings)),
        ], $rows);
    }

    /**
     * Bookings that were not confirmed (waiting, failed, expired) in the last days: did the guest pay anyway?
     *
     * @return array<int,array<string,mixed>>
     */
    private function unconfirmedSubjects(): array
    {
        $rows = DB::select("
            select coalesce(bg.group_ref, cast(b.booking_number as char)) as tran_id,
                   sum(round(b.amount * 100)) as paisa, max(b.created_at) as at,
                   group_concat(distinct b.booking_number order by b.booking_number) as bookings
            from bookings b
            left join booking_groups bg on bg.id = b.booking_group_id
            where b.deleted_at is null
              and b.payment_status in ('pending', 'failed', 'cancelled')
              and b.created_at >= ?
              and b.created_at <= ?
              and not exists (select 1 from transactions t where t.booking_id = b.booking_number and t.status in ('completed', 'refunded') and t.deleted_at is null)
            group by coalesce(bg.group_ref, cast(b.booking_number as char))
        ", [now()->subDays(self::UNCONFIRMED_DAYS)->utc(), now()->subMinutes(self::UNCONFIRMED_GRACE_MINUTES)->utc()]);

        return array_map(fn ($r) => [
            'kind' => 'unconfirmed', 'tran_id' => (string) $r->tran_id, 'our_status' => 'unconfirmed', 'paisa' => (int) $r->paisa,
            'at' => $r->at, 'bookings' => array_map('intval', explode(',', (string) $r->bookings)),
        ], $rows);
    }

    /** Refunds completed through SSLCommerz itself (the simulated local ones never reached it) */
    private function refundSubjects(): array
    {
        return RefundRequest::query()
            ->where('status', RefundRequest::STATUS_COMPLETED)
            ->whereNotNull('gateway_refund_id')->where('gateway_refund_id', 'not like', 'LOCAL-REFUND-%')
            ->where('completed_at', '>=', now()->subDays(self::KEEP_CHECKING_DAYS))
            ->get()->map(fn (RefundRequest $r) => [
                'kind' => 'refund', 'tran_id' => 'refund:' . $r->id, 'our_status' => 'completed', 'paisa' => MoneySplitter::toPaisa($r->approved_amount),
                'at' => $r->completed_at?->toDateTimeString(), 'bookings' => [(int) optional($r->booking)->booking_number], 'gateway_ref' => $r->gateway_refund_id,
            ])->all();
    }

    // ────────────────────────────────────────────────────────  asking and judging

    private function checkPayment(array $s): GatewayCheck
    {
        $answer = $this->gateway->query($s['tran_id']);

        if (!$answer['reachable']) {
            return $this->unreachable($s, $answer['configured']);
        }

        // the element that says "paid" (an unknown id comes back as INVALID)
        $paid   = collect($answer['elements'])->first(fn ($e) => in_array($e['status'] ?? null, ['VALID', 'VALIDATED'], true));
        $other  = collect($answer['elements'])->pluck('status')->filter()->unique()->implode(', ');
        $data   = ['gateway_status' => $paid['status'] ?? ($other ?: 'none'), 'gateway_paisa' => null, 'gateway_fee_paisa' => null, 'bank_tran_id' => null, 'gateway_date' => null, 'ledger_fee_paisa' => null];

        if ($paid) {
            $gross = MoneySplitter::toPaisa($paid['amount'] ?? null);
            $data['gateway_paisa']     = $gross;
            $data['gateway_fee_paisa'] = isset($paid['store_amount']) && is_numeric($paid['store_amount']) ? $gross - MoneySplitter::toPaisa($paid['store_amount']) : null;
            $data['bank_tran_id']      = $paid['bank_tran_id'] ?? null;
            $data['gateway_date']      = $paid['tran_date'] ?? null;
        }

        if ($s['kind'] === 'unconfirmed') {
            [$result, $problem] = $paid
                ? ['paid_unconfirmed', 'The guest PAID on SSLCommerz (' . $this->taka($data['gateway_paisa']) . ', bank reference ' . ($data['bank_tran_id'] ?? '?') . '), but this booking is not confirmed here. The money is with you, the guest has no booking.']
                : [GatewayCheck::OK, null];

            return $this->save($s, $data, $result, $problem);
        }

        $data['ledger_fee_paisa'] = $this->ledgerFee($s['bookings']);

        if (!$paid) {
            return $this->save($s, $data, 'not_at_gateway', 'We recorded this payment (' . $this->taka($s['paisa']) . '), but SSLCommerz has no valid payment for it' . ($other ? " (it says: $other)" : '') . '.');
        }
        if ($data['gateway_paisa'] !== $s['paisa']) {
            return $this->save($s, $data, 'amount_differs', 'We recorded ' . $this->taka($s['paisa']) . ', SSLCommerz says the guest paid ' . $this->taka($data['gateway_paisa']) . '.');
        }
        if ($data['ledger_fee_paisa'] !== null && $data['gateway_fee_paisa'] !== null && $data['ledger_fee_paisa'] !== $data['gateway_fee_paisa']) {
            return $this->save($s, $data, 'fee_differs', 'SSLCommerz kept ' . $this->taka($data['gateway_fee_paisa']) . ', our records say ' . $this->taka($data['ledger_fee_paisa']) . '.');
        }

        // a payment we say is still paid, but SSLCommerz lists as refunded (money went back without a refund here)
        $refunded = collect($answer['elements'])->contains(fn ($e) => preg_match('/REFUND/i', (string) ($e['status'] ?? '')) === 1);
        if ($refunded && $s['our_status'] === 'completed') {
            return $this->save($s, $data, 'status_differs', 'SSLCommerz lists this payment as refunded, but no refund is recorded here.');
        }

        return $this->save($s, $data, GatewayCheck::OK, null);
    }

    private function checkRefund(array $s): GatewayCheck
    {
        try {
            $answer = app(SslCommerzRefundService::class)->status($s['gateway_ref']);
        } catch (Throwable $e) {
            return $this->unreachable($s, true);
        }

        $data = ['gateway_status' => $answer['status'], 'gateway_paisa' => null, 'gateway_fee_paisa' => null, 'bank_tran_id' => null, 'gateway_date' => null, 'ledger_fee_paisa' => null];

        return $answer['status'] === 'completed'
            ? $this->save($s, $data, GatewayCheck::OK, null)
            : $this->save($s, $data, 'status_differs', "We recorded this refund as completed, but SSLCommerz says it is: {$answer['status']}.");
    }

    private function unreachable(array $s, bool $configured): GatewayCheck
    {
        $old      = GatewayCheck::where('tran_id', $s['tran_id'])->first();
        $attempts = ($old?->attempts ?? 0) + 1;

        // A blip must not wipe out an earlier verdict, nor the note an admin wrote about it: only the count of failed tries goes up.
        // (A payment that has never been asked about, or has not been reachable for many tries in a row, becomes a "could not be checked" row.)
        if ($old && $old->result !== 'gateway_error' && ($old->result !== GatewayCheck::OK || $attempts < self::UNREACHABLE_LIMIT)) {
            $old->forceFill(['attempts' => $attempts])->save();
            $old->touch();

            return $old;
        }

        // a few failed tries are not a finding: the gateway may simply be down for a moment
        $row = $this->save($s, [], 'gateway_error', $configured
            ? "SSLCommerz could not be asked ($attempts time" . ($attempts === 1 ? '' : 's') . ' in a row).'
            : 'SSLCommerz is not set up here (no store id / password).', $attempts);

        return $row;
    }

    private function save(array $s, array $data, string $result, ?string $problem, int $attempts = 0): GatewayCheck
    {
        $old = GatewayCheck::where('tran_id', $s['tran_id'])->first();

        // a look that was recorded for one verdict does not cover a different one
        $keepResolved = $old && $old->result === $result;

        return GatewayCheck::updateOrCreate(['tran_id' => $s['tran_id']], [
            'kind' => $s['kind'] === 'refund' ? 'refund' : 'payment', 'our_status' => $s['our_status'], 'our_paisa' => $s['paisa'],
            'bookings' => $s['bookings'], 'subject_at' => $s['at'] ? CarbonImmutable::parse($s['at'], 'UTC') : null,
            'result' => $result, 'problem' => $problem, 'attempts' => $attempts, 'checked_at' => now(),
            'resolved_at' => $keepResolved ? $old->resolved_at : null, 'resolved_by' => $keepResolved ? $old->resolved_by : null,
            'resolved_by_name' => $keepResolved ? $old->resolved_by_name : null, 'resolved_note' => $keepResolved ? $old->resolved_note : null,
        ] + ($result === 'gateway_error' && $old ? [] : $this->gatewayColumns($data)));
    }

    private function gatewayColumns(array $data): array
    {
        $cut = fn ($v, int $n) => $v === null ? null : mb_substr((string) $v, 0, $n);
        $all = array_merge(['gateway_status' => null, 'gateway_paisa' => null, 'gateway_fee_paisa' => null, 'ledger_fee_paisa' => null, 'bank_tran_id' => null, 'gateway_date' => null], $data);

        return ['gateway_status' => $cut($all['gateway_status'], 30), 'bank_tran_id' => $cut($all['bank_tran_id'], 80), 'gateway_date' => $cut($all['gateway_date'], 30)] + $all;
    }

    /** What our money records say SSLCommerz kept for these bookings; null when any of them has no fee on record */
    private function ledgerFee(array $bookingNumbers): ?int
    {
        $rows = DB::table('ledger_entries')->whereIn('booking_number', $bookingNumbers)->where('type', 'payment')->pluck('gateway_fee_paisa');

        return $rows->isEmpty() || $rows->contains(null) ? null : (int) $rows->sum();
    }

    /** Is a "SSLCommerz could not be reached" verdict now a problem (it has happened many times in a row)? */
    public static function unreachableIsProblem(GatewayCheck $c): bool
    {
        return $c->result === 'gateway_error' && $c->attempts >= self::UNREACHABLE_LIMIT;
    }

    private function taka(?int $paisa): string
    {
        return 'BDT ' . number_format(($paisa ?? 0) / 100, 2);
    }
}
