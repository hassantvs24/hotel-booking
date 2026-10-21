<?php

namespace App\Services\Money;

use App\Models\LedgerEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Reads the ledger and answers the admin's questions: how much came in, what is mine (the commission), what do I owe each vendor.
 * Every number is whole paisa, taken straight from ledger lines. Nothing is recalculated here.
 *
 * Each ledger line's vendor share lands in one bucket:
 *   ready     the guest has checked out and the wait is over: payable now
 *   not_due   the guest has not checked out yet, or the wait is not over
 *   held      a person must look first (the line is flagged "needs_review", or there is no check-out date)
 *   paid      the line is in a payout statement the admin has marked PAID: the vendor already has this money
 * The money buckets always add up to what came in:
 *   gross = platform_service (always 0) + commission + vendor(ready + not_due + held + paid_out)
 * The VAT and the service charge are inside the vendor's part; they are only reported (the vendor sees them), not a bucket.
 */
class MoneyReport
{
    public function __construct(private readonly MoneyRules $rules, private readonly MoneyChecks $checks) {}

    /** Forget the bookings that were found to disagree with their money (call after changing a booking) */
    public function forget(): void
    {
        $this->checks->forget();
    }

    /** Why a booking's money is held back because the booking itself changed, or null */
    public function problemCode(?int $bookingNumber): ?string
    {
        $this->forget();

        return $this->checks->reasonFor($bookingNumber);
    }

    /** Today, in Dhaka, as Y-m-d */
    public function today(): string
    {
        return CarbonImmutable::now(MoneyRules::TZ)->toDateString();
    }

    /** The last check-out day whose money is payable today (check-out + wait days <= today) */
    public function payableCutoff(): string
    {
        return CarbonImmutable::now(MoneyRules::TZ)->subDays($this->rules->waitDays())->toDateString();
    }

    /** The day a booking's money becomes payable */
    public function payableOn(?string $checkout): ?string
    {
        return $checkout ? CarbonImmutable::parse($checkout)->addDays($this->rules->waitDays())->toDateString() : null;
    }

    /** Which bucket one ledger line belongs to */
    public function bucketOf(LedgerEntry $e): string
    {
        if ($this->isPaidOut($e->id)) {
            return 'paid';
        }

        if ($e->hasFlag('needs_review') || $e->hasFlag('gateway_risk') || !$e->checkout_date || $this->checks->reasonFor($e->booking_number)) {
            return 'held';
        }

        return $e->checkout_date->toDateString() <= $this->payableCutoff() ? 'ready' : 'not_due';
    }

    /** @var array<int,bool> ledger line id => is in a PAID payout statement */
    private array $paidOut = [];

    /** Look up, in one query, which of these lines have already been paid out to the vendor (call before presenting a page of lines) */
    public function loadPaidOut(iterable $entries): void
    {
        $ids = collect($entries)->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        if (!$ids) {
            return;
        }

        $paid = DB::table('payout_statement_lines as pl')
            ->join('payout_statements as ps', 'ps.id', '=', 'pl.statement_id')
            ->whereIn('pl.ledger_entry_id', $ids)->where('pl.active', 1)->where('ps.status', 'paid')
            ->pluck('pl.ledger_entry_id')->flip();

        foreach ($ids as $id) {
            $this->paidOut[$id] = isset($paid[$id]);
        }
    }

    public function isPaidOut(int $ledgerId): bool
    {
        if (!array_key_exists($ledgerId, $this->paidOut)) {
            $this->loadPaidOut([['id' => $ledgerId]]);
        }

        return $this->paidOut[$ledgerId] ?? false;
    }

    /** Forget what was looked up (after a payout was paid or reversed) */
    public function forgetPaidOut(): void
    {
        $this->paidOut = [];
    }

    /**
     * SQL: this ledger line is in a PAID payout statement. Only valid in a query built by scope(), which joins the paid
     * lines as `paid_x` (a join, not a subquery, so it can be used inside the sums of a GROUP BY query).
     */
    public function paidSql(): string
    {
        return 'paid_x.paid_entry_id is not null';
    }

    /** SQL: this ledger line is in any LIVE payout statement (a draft or a paid one) */
    public static function inStatementSql(): string
    {
        return 'exists (select 1 from payout_statement_lines psl where psl.ledger_entry_id = ledger_entries.id and psl.active = 1)';
    }

    /** SQL: this line is held back (a person must look first) */
    public function heldSql(): string
    {
        $unsafe = array_map('intval', array_keys($this->checks->unsafe()));

        return "(coalesce(json_contains(flags, '\"needs_review\"'), 0) = 1 or coalesce(json_contains(flags, '\"gateway_risk\"'), 0) = 1 or checkout_date is null" . ($unsafe ? ' or booking_number in (' . implode(',', $unsafe) . ')' : '') . ')';
    }

    /** SQL: not held, and the guest checked out long enough ago: payable now (whether or not it is already in a statement) */
    public function readySql(): string
    {
        $cut = DB::getPdo()->quote($this->payableCutoff());

        return '(not ' . $this->heldSql() . " and checkout_date <= {$cut})";
    }

    /**
     * Totals for a period (Dhaka days, both ends included; null = no limit).
     *
     * @return array<string,int>
     */
    public function totals(?string $from = null, ?string $to = null, ?int $vendorId = null): array
    {
        $this->forget();
        $row = $this->scope($from, $to, $vendorId)->selectRaw($this->sums())->first();
        $out = [];

        foreach ($this->columns() as $name) {
            $out[$name] = (int) ($row->{$name} ?? 0);
        }

        return $out;
    }

    /** One row per vendor, with what is ready to pay, not due yet and on hold */
    public function perVendor(?string $from = null, ?string $to = null, ?string $search = null): array
    {
        $this->forget();
        $q = $this->scope($from, $to)
            ->selectRaw('vendor_id, max(vendor_name) as vendor_name, count(distinct booking_number) as bookings, count(distinct property_id) as properties, ' . $this->sums())
            ->groupBy('vendor_id')
            ->orderByRaw('max(vendor_name)');

        if ($search) {
            $q->where('vendor_name', 'like', '%' . $search . '%');
        }

        return $q->get()->map(function ($r) {
            $row = ['vendor_id' => $r->vendor_id, 'vendor_name' => $r->vendor_name ?: 'Unknown vendor', 'bookings' => (int) $r->bookings, 'properties' => (int) $r->properties];

            foreach ($this->columns() as $name) {
                $row[$name] = (int) $r->{$name};
            }

            return $row;
        })->all();
    }

    /** Ledger lines, newest first, for a vendor, a booking or a period */
    public function lines(array $filter = []): Builder
    {
        $this->forget();
        $q = LedgerEntry::query()->orderByDesc('occurred_at')->orderByDesc('id');

        if (!empty($filter['vendor_id'])) {
            $q->where('vendor_id', $filter['vendor_id']);
        }
        if (!empty($filter['booking_number'])) {
            $q->where('booking_number', $filter['booking_number']);
        }
        if (!empty($filter['type'])) {
            $q->where('type', $filter['type']);
        }
        if (!empty($filter['from'])) {
            $q->where('occurred_on', '>=', $filter['from']);
        }
        if (!empty($filter['to'])) {
            $q->where('occurred_on', '<=', $filter['to']);
        }
        if (!empty($filter['flagged'])) {
            $unsafe = array_keys($this->checks->unsafe());
            $q->where(fn ($w) => $w->whereNotNull('flags')->orWhereIn('booking_number', $unsafe));
        }
        if (!empty($filter['search'])) {
            $s = '%' . $filter['search'] . '%';
            $q->where(fn ($w) => $w->where('booking_number', 'like', $s)->orWhere('property_name', 'like', $s)->orWhere('vendor_name', 'like', $s));
        }

        return $q;
    }

    /** One line of a ledger line in the shape the screens use, with its bucket */
    public function present(LedgerEntry $e): array
    {
        return [
            'id'                => $e->id,
            'type'              => $e->type,
            'booking_number'    => $e->booking_number,
            'property_id'       => $e->property_id,
            'property_name'     => $e->property_name,
            'vendor_id'         => $e->vendor_id,
            'vendor_name'       => $e->vendor_name,
            'occurred_on'       => $e->occurred_on?->toDateString(),
            'occurred_at'       => $e->occurred_at?->toIso8601String(),
            'checkout_date'     => $e->checkout_date?->toDateString(),
            'payable_on'        => $this->payableOn($e->checkout_date?->toDateString()),
            'bucket'            => $this->bucketOf($e),
            'gross_paisa'       => $e->gross_paisa,
            'base_paisa'        => $e->base_paisa,
            'service_paisa'     => $e->service_paisa,
            'vat_paisa'         => $e->vat_paisa,
            'platform_service_paisa' => $e->platform_service_paisa,
            'commission_paisa'  => $e->commission_paisa,
            'vendor_paisa'      => $e->vendor_paisa,
            'gateway_fee_paisa' => $e->gateway_fee_paisa,
            'commission_percent' => $e->commission_percent === null ? null : (float) $e->commission_percent,
            'service_charge_to' => $e->service_charge_to,
            'is_test'           => $e->is_test,
            'flags'             => $e->flags ?? [],
            'problem'           => $this->checks->reasonFor($e->booking_number),
        ];
    }

    /**
     * Bookings whose money needs a person to look: a line that cannot be split, or a booking that no longer agrees
     * with its money. One row per booking, with what is held back.
     *
     * @return array<int,array<string,mixed>>
     */
    public function problems(int $limit = 50): array
    {
        $this->forget();
        $unsafe = $this->checks->unsafe();
        $lines  = LedgerEntry::query()
            ->where(fn ($w) => $w->whereRaw("coalesce(json_contains(flags, '\"needs_review\"'), 0) = 1 or coalesce(json_contains(flags, '\"gateway_risk\"'), 0) = 1")->orWhereIn('booking_number', array_keys($unsafe)))
            ->get()
            ->groupBy('booking_number');

        $this->loadPaidOut($lines->flatten(1));
        $rows = [];

        foreach ($lines as $number => $group) {
            $first  = $group->first();
            $code   = $unsafe[$number] ?? null;
            $flag   = collect($first->flags ?? [])->first(fn ($f) => $f !== 'needs_review') ?? 'needs_review';
            $paid   = (int) $group->filter(fn ($l) => $this->isPaidOut($l->id))->sum('vendor_paisa');
            $unpaid = (int) $group->sum('vendor_paisa') - $paid;
            $base   = ['booking_number' => $number ?: null, 'property_name' => $first->property_name, 'vendor_name' => $first->vendor_name];

            if ($unpaid !== 0) {
                $rows[] = $base + ['code' => $code ?? $flag, 'text' => $code ? MoneyChecks::text($code) : self::flagText($flag), 'held_paisa' => $unpaid];
            }

            // the vendor already has this money and the booking has changed since: nothing to hold, but a person must know
            if ($paid !== 0 && $code) {
                $rows[] = $base + ['code' => 'paid_then_changed', 'text' => 'The vendor was already paid for this booking, and the booking has changed since. ' . MoneyChecks::text($code), 'held_paisa' => $paid];
            }
        }

        return collect($rows)->sortByDesc('held_paisa')->take($limit)->values()->all();
    }

    private static function flagText(string $flag): string
    {
        return [
            'needs_review'     => 'The payment could not be split into room price and taxes.',
            'gateway_risk'     => 'SSLCommerz marked this payment as risky. Check it before paying the vendor.',
            'amount_mismatch'  => 'The payment is not the same amount as the booking.',
            'no_tax_snapshot'  => 'The booking saved no VAT or service charge details.',
            'booking_missing'  => 'The booking no longer exists.',
            'property_missing' => 'The property no longer exists.',
            'vendor_missing'   => 'The property has no owner on record.',
        ][$flag] ?? $flag;
    }

    /** Completed payments from the books' start date that the ledger has not written down yet (a backlog, or the last minute) */
    public function waitingToBeRecorded(): int
    {
        return \App\Models\Transaction::query()
            ->whereIn('status', ['completed', 'refunded'])
            ->where(fn ($q) => $q
                ->where(fn ($w) => $w->where('status', 'completed')->where('updated_at', '>=', $this->rules->startsAt()->utc()))
                ->orWhere(fn ($w) => $w->where('status', 'refunded')->where('created_at', '>=', $this->rules->startsAt()->utc())))
            ->whereNotExists(fn ($q) => $q->from('ledger_entries')->select(DB::raw(1))->where('ledger_entries.type', LedgerEntry::TYPE_PAYMENT)->whereColumn('ledger_entries.transaction_id', 'transactions.id'))
            ->count();
    }

    /** The checks behind the "your books are in order" line */
    public function health(): array
    {
        $this->forget();
        $off = LedgerEntry::query()
            ->whereRaw('(platform_service_paisa + commission_paisa + vendor_paisa) <> gross_paisa')
            ->count();
        $parts = LedgerEntry::query()
            ->whereRaw('(base_paisa + service_paisa + vat_paisa) <> gross_paisa')
            ->count();

        $recount = app(MoneyRecount::class)->run();

        return [
            'lines'            => LedgerEntry::count(),
            'recount'          => $recount,
            'recount_ok'       => $recount['ok'],
            'lines_not_adding' => $off + $parts,
            'needs_review'     => LedgerEntry::query()->whereRaw("coalesce(json_contains(flags, '\"needs_review\"'), 0) = 1")->count(),
            'status_mismatch'  => count($this->checks->unsafe()),
            'to_look_at'       => count($this->problems(1000)),
            'waiting'          => $this->waitingToBeRecorded(),
            'test_lines'       => LedgerEntry::where('is_test', true)->count(),
            'payout_problems'  => app(PayoutService::class)->problems(),
            'balanced'         => $off + $parts === 0,
        ];
    }

    // ────────────────────────────────────────────────────────

    private function scope(?string $from, ?string $to, ?int $vendorId = null): Builder
    {
        $q = LedgerEntry::query()->leftJoin(
            DB::raw("(select distinct psl.ledger_entry_id as paid_entry_id from payout_statement_lines psl join payout_statements pst on pst.id = psl.statement_id where psl.active = 1 and pst.status = 'paid') as paid_x"),
            'paid_x.paid_entry_id', '=', 'ledger_entries.id'
        );

        if ($from) { $q->where('ledger_entries.occurred_on', '>=', $from); }
        if ($to)   { $q->where('ledger_entries.occurred_on', '<=', $to); }
        if ($vendorId) { $q->where('ledger_entries.vendor_id', $vendorId); }

        return $q;
    }

    private function columns(): array
    {
        return ['gross', 'vat', 'service', 'platform_service', 'commission', 'vendor', 'gateway_fee', 'ready', 'not_due', 'held', 'paid_out', 'paid_in', 'refunded'];
    }

    /** The aggregate expressions. "held" is judged per line, so the buckets are summed with CASE. */
    private function sums(): string
    {
        $cut     = DB::getPdo()->quote($this->payableCutoff());
        $isPaid  = '(' . $this->paidSql() . ')';
        $isHeld  = '(not ' . $isPaid . ' and ' . $this->heldSql() . ')';
        $isReady = "(not {$isPaid} and not {$this->heldSql()} and checkout_date <= {$cut})";

        return implode(', ', [
            'coalesce(sum(gross_paisa), 0) as gross',
            'coalesce(sum(vat_paisa), 0) as vat',                  // (inside the vendors' money: only reported)
            'coalesce(sum(service_paisa), 0) as service',          // (the same for the service charge)
            'coalesce(sum(platform_service_paisa), 0) as platform_service',
            'coalesce(sum(commission_paisa), 0) as commission',
            'coalesce(sum(vendor_paisa), 0) as vendor',
            'coalesce(sum(gateway_fee_paisa), 0) as gateway_fee',
            "coalesce(sum(case when {$isReady} then vendor_paisa else 0 end), 0) as ready",
            "coalesce(sum(case when not {$isPaid} and not {$this->heldSql()} and not {$isReady} then vendor_paisa else 0 end), 0) as not_due",
            "coalesce(sum(case when {$isHeld} then vendor_paisa else 0 end), 0) as held",
            "coalesce(sum(case when {$isPaid} then vendor_paisa else 0 end), 0) as paid_out",
            "coalesce(sum(case when type = 'payment' then gross_paisa else 0 end), 0) as paid_in",
            "coalesce(-sum(case when type = 'refund' then gross_paisa else 0 end), 0) as refunded",
        ]);
    }
}
