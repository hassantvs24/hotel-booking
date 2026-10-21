<?php

namespace App\Services\Money;

use App\Models\PayoutStatement;
use App\Models\Property;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * The money as ONE vendor sees it, and as the platform sees its own income. Everything is whole paisa, taken from the
 * ledger by MoneyReport: nothing is worked out again here.
 *
 * What a vendor may see: their own totals and their own statements that were PAID (or later reversed). Never a draft, never
 * another vendor, and nothing internal (notes, who prepared it, holds, the history of the statement).
 */
class VendorMoney
{
    public function __construct(
        private readonly MoneyReport $report,
        private readonly MoneyRules $rules,
        private readonly PayoutService $payouts,
    ) {}

    /** What the vendor has been paid, what comes next and what comes later */
    public function summary(User $vendor): array
    {
        $this->report->forget();
        $t = $this->report->totals(null, null, $vendor->id);
        [$from, $to] = $this->thisMonth();
        $m        = $this->report->totals($from, $to, $vendor->id);
        $schedule = $this->payouts->schedule();
        $minimum  = $this->payouts->minimumPaisa();
        $last     = PayoutStatement::where('vendor_id', $vendor->id)->where('status', PayoutStatement::PAID)->orderByDesc('paid_at')->orderByDesc('id')->first();

        return [
            'since'             => $this->rules->startsAt()->toDateString(),
            // the money, in the vendor's own words
            'paid_out_paisa'    => $t['paid_out'],                      // already sent to them
            'next_payout_paisa' => $t['ready'],                         // ready: goes in the next payout
            'next_payout_on'    => $schedule['next_payout_on'],
            'minimum_paisa'     => $minimum,
            'next_payout_waits' => $t['ready'] > 0 && $t['ready'] < $minimum,
            'later_paisa'       => $t['not_due'],                       // the stay is not over, or the wait after it is not over
            'review_paisa'      => $t['held'],                          // waiting for a check by the platform
            'wait_days'         => $this->rules->waitDays(),
            'share_paisa'       => $t['vendor'],                        // everything that is theirs: paid + next + later + under review
            // how it came about
            'guest_paid_paisa'  => $t['gross'],                         // what the guests paid, after refunds
            'commission_paisa'  => $t['commission'],
            'service_paisa'     => $t['service'],                       // inside their share
            'vat_paisa'         => $t['vat'],                           // inside their share
            'commission_rates'  => $this->rates($vendor),
            'month'             => ['from' => $from, 'to' => $to, 'share_paisa' => $m['vendor'], 'commission_paisa' => $m['commission'], 'guest_paid_paisa' => $m['gross']],
            'last_payout'       => $last ? ['number' => $last->number, 'amount_paisa' => (int) $last->vendor_paisa, 'paid_at' => MoneyRules::local($last->paid_at)] : null,
        ];
    }

    /** The platform's own income: only the commission (the VAT and the service charge are the vendors') */
    public function platform(): array
    {
        $this->report->forget();
        $t = $this->report->totals();
        [$from, $to] = $this->thisMonth();
        $m = $this->report->totals($from, $to);

        return [
            'since'                  => $this->rules->startsAt()->toDateString(),
            'guest_paid_paisa'       => $t['gross'],                                       // what came in, after refunds
            'commission_paisa'       => $t['commission'],                                  // the platform's income
            'gateway_fee_paisa'      => $t['gateway_fee'],                                 // what SSLCommerz kept (known fees only)
            'net_paisa'              => $t['commission'] - $t['gateway_fee'],
            'owed_to_vendors_paisa'  => $t['vendor'] - $t['paid_out'],                     // theirs, not sent yet
            'paid_to_vendors_paisa'  => $t['paid_out'],
            'month'                  => ['from' => $from, 'to' => $to, 'commission_paisa' => $m['commission'], 'net_paisa' => $m['commission'] - $m['gateway_fee'], 'guest_paid_paisa' => $m['gross']],
        ];
    }

    /** The vendor's PAID (and later reversed) statements, newest first */
    public function statements(User $vendor, int $page = 1, int $perPage = 20): array
    {
        $p = PayoutStatement::where('vendor_id', $vendor->id)->whereIn('status', [PayoutStatement::PAID, PayoutStatement::REVERSED])
            ->orderByDesc('paid_at')->orderByDesc('id')->paginate($perPage, ['*'], 'page', max(1, $page));

        return [
            'statements' => $p->getCollection()->map(fn (PayoutStatement $s) => $this->row($s))->values()->all(),
            'meta'       => ['page' => $p->currentPage(), 'last_page' => $p->lastPage(), 'total' => $p->total(), 'per_page' => $p->perPage()],
        ];
    }

    /** One of the vendor's own statements, or null (someone else's, a draft and a cancelled one are all "not found") */
    public function find(User $vendor, int $id): ?PayoutStatement
    {
        return PayoutStatement::where('id', $id)->where('vendor_id', $vendor->id)->whereIn('status', [PayoutStatement::PAID, PayoutStatement::REVERSED])->first();
    }

    /** The statement with every booking behind it, as the vendor sees it */
    public function statement(PayoutStatement $s): array
    {
        $lines = $s->lines()->where(fn ($q) => $s->isPaid() ? $q->where('active', 1) : $q)->orderBy('booking_number')->orderBy('id')->get();

        $bookings = $lines->groupBy('booking_number')->map(function ($g, $number) {
            $f = $g->first();

            return [
                'booking_number'     => $number ?: null,
                'property_name'      => $f->property_name,
                'checkout_date'      => $f->checkout_date?->toDateString(),
                'gross_paisa'        => (int) $g->sum('gross_paisa'),
                'base_paisa'         => (int) $g->sum('base_paisa'),
                'service_paisa'      => (int) $g->sum('service_paisa'),
                'vat_paisa'          => (int) $g->sum('vat_paisa'),
                'commission_percent' => $f->commission_percent === null ? null : (float) $f->commission_percent,
                'commission_paisa'   => (int) $g->sum('commission_paisa'),
                'vendor_paisa'       => (int) $g->sum('vendor_paisa'),
            ];
        })->values()->all();

        $rates = $lines->pluck('commission_percent')->filter(fn ($r) => $r !== null)->map(fn ($r) => (float) $r)->unique();
        $acct  = is_array($s->account_snapshot) ? $s->account_snapshot : null;

        return $this->row($s) + [
            'gross_paisa'        => (int) $s->gross_paisa,
            'base_paisa'         => (int) $s->base_paisa,
            'service_paisa'      => (int) $s->service_paisa,
            'vat_paisa'          => (int) $s->vat_paisa,
            'commission_paisa'   => (int) $s->commission_paisa,
            'commission_percent' => $rates->count() === 1 ? $rates->first() : null,
            'bookings'           => $bookings,
            // where it was sent: the account's name and the LAST four digits only
            'account'            => $acct ? ['method' => $acct['method'] ?? null, 'holder_name' => $acct['holder_name'] ?? null, 'bank_name' => $acct['bank_name'] ?? null, 'account_number' => PayoutAccountService::mask($acct['account_number'] ?? null)] : null,
        ];
    }

    // ────────────────────────────────────────────────────────

    private function row(PayoutStatement $s): array
    {
        return [
            'id' => $s->id, 'number' => $s->number, 'status' => $s->status, 'amount_paisa' => (int) $s->vendor_paisa, 'bookings_count' => (int) $s->bookings_count,
            'paid_at' => MoneyRules::local($s->paid_at), 'method' => $s->method, 'reference' => $s->reference, 'reversed_at' => MoneyRules::local($s->reversed_at),
        ];
    }

    /** The commission rate of each of the vendor's properties as it stands today */
    private function rates(User $vendor): array
    {
        return Property::where('user_id', $vendor->id)->orderBy('name')->get(['id', 'name', 'commission_percent'])->map(fn (Property $p) => [
            'property_id' => $p->id, 'property_name' => $p->name, 'percent' => $this->rules->commissionPercentFor($p), 'own' => $p->commission_percent !== null,
        ])->all();
    }

    /** @return array{0:string,1:string} the first day of this month and today (Dhaka) */
    private function thisMonth(): array
    {
        $today = CarbonImmutable::now(MoneyRules::TZ)->startOfDay();

        return [$today->startOfMonth()->toDateString(), $today->toDateString()];
    }
}
