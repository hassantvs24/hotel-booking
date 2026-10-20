<?php

namespace App\Http\Controllers\API\Admin\Money;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Services\Money\LedgerSync;
use App\Services\Money\MoneyChecks;
use App\Services\Money\MoneyReport;
use App\Services\Money\MoneyRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The admin's money screens: overview, what each vendor is owed, and the lines behind every number.
 * Platform admin only. All amounts are whole paisa.
 */
class MoneyReportController extends BaseController
{
    public function __construct(
        private readonly MoneyReport $report,
        private readonly MoneyRules $rules,
        private readonly LedgerSync $ledger,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $this->ledger->run(2.0);   // pick up anything that came in since the last minute

        [$from, $to, $label] = $this->period($request);

        return $this->sendSuccess([
            'period'    => ['from' => $from, 'to' => $to, 'label' => $label, 'key' => $request->input('period', 'week')],
            'totals'    => $this->report->totals($from, $to),
            'health'    => $this->report->health(),
            'problems'  => $this->report->problems(8),
            'settings'  => $this->settingsNote(),
            'today'     => $this->report->today(),
            'reconcile' => ReconcileController::summary(),
        ]);
    }

    public function vendors(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $this->ledger->run(2.0);

        return $this->sendSuccess([
            'vendors'  => $this->report->perVendor(null, null, $request->input('search')),
            'totals'   => $this->report->totals(),
            'settings' => $this->settingsNote(),
        ]);
    }

    /** One vendor: their totals and every booking behind them */
    public function vendor(Request $request, int $vendor): JsonResponse
    {
        $this->adminOnly($request);
        $this->ledger->run(2.0);

        $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:5,100']]);

        // the bookings of this vendor, newest first, one page at a time
        $page = LedgerEntry::query()
            ->where('vendor_id', $vendor)
            ->selectRaw('booking_number, max(occurred_at) as last_at, max(vendor_name) as vendor_name')
            ->groupBy('booking_number')
            ->orderByDesc('last_at')
            ->orderByDesc('booking_number')
            ->paginate((int) $request->input('per_page', 50));

        abort_if($page->total() === 0, 404, 'No money has been recorded for this vendor yet.');

        $entries = $this->report->lines(['vendor_id' => $vendor])->reorder()->whereIn('booking_number', $page->pluck('booking_number'))->orderBy('id')->get();
        $this->report->loadPaidOut($entries);

        $bookings = $entries->groupBy('booking_number')->map(function ($lines) {
            $payment = $lines->firstWhere('type', LedgerEntry::TYPE_PAYMENT) ?? $lines->first();
            $row     = $this->report->present($payment);

            foreach (['gross', 'base', 'service', 'vat', 'platform_service', 'commission', 'vendor'] as $part) {
                $row[$part . '_paisa'] = (int) $lines->sum($part . '_paisa');
            }
            $row['refunded_paisa'] = -1 * (int) $lines->where('type', LedgerEntry::TYPE_REFUND)->sum('gross_paisa');
            $row['paid_in_paisa']  = (int) $lines->where('type', LedgerEntry::TYPE_PAYMENT)->sum('gross_paisa');
            $row['lines']          = $lines->count();
            $row['flags']          = $lines->flatMap(fn ($l) => $l->flags ?? [])->unique()->values()->all();

            return $row;
        })->sortByDesc('occurred_at')->values();

        return $this->sendSuccess([
            'vendor'   => ['id' => $vendor, 'name' => ($page->first()?->vendor_name ?? LedgerEntry::where('vendor_id', $vendor)->value('vendor_name')) ?: 'Unknown vendor'],
            'totals'   => $this->report->totals(null, null, $vendor),   // all of the vendor's bookings, not just this page
            'bookings' => $bookings,
            'meta'     => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'settings' => $this->settingsNote(),
        ]);
    }

    /** Every ledger line for one booking, with the booking itself if it still exists */
    public function booking(Request $request, int $number): JsonResponse
    {
        $this->adminOnly($request);

        $lines = $this->report->lines(['booking_number' => $number])->reorder('occurred_at')->orderBy('id')->get();
        abort_if($lines->isEmpty(), 404, 'This booking is not in the money records.');
        $this->report->loadPaidOut($lines);

        $booking = Booking::withTrashed()->with('user:id,name')->where('booking_number', $number)->first();

        return $this->sendSuccess([
            'booking_number' => $number,
            'booking'        => $booking ? [
                'guest'          => $booking->user?->name,
                'checkin'        => $booking->checkin,
                'checkout'       => $booking->checkout,
                'status'         => $booking->status,
                'payment_status' => $booking->payment_status,
                'deleted'        => $booking->trashed(),
            ] : null,
            'problem'        => ($code = $this->report->problemCode($number)) ? ['code' => $code, 'text' => MoneyChecks::text($code)] : null,
            'hold'           => ($h = DB::table('money_holds')->where('booking_number', $number)->first(['note', 'held_by_name', 'created_at'])) ? ['note' => $h->note, 'held_by_name' => $h->held_by_name, 'created_at' => MoneyRules::local($h->created_at)] : null,
            'statements'     => DB::table('payout_statement_lines as pl')->join('payout_statements as ps', 'ps.id', '=', 'pl.statement_id')->where('pl.booking_number', $number)->where('pl.active', 1)->distinct()->get(['ps.id', 'ps.number', 'ps.status']),
            'reviews'        => DB::table('money_reviews')->where('booking_number', $number)->orderBy('id')->get(['reason', 'note', 'reviewed_by_name', 'created_at'])
                ->map(fn ($r) => ['reason' => $r->reason, 'text' => MoneyChecks::text($r->reason), 'note' => $r->note, 'by' => $r->reviewed_by_name, 'at' => MoneyRules::local($r->created_at)]),
            'lines'          => $lines->map(fn ($l) => $this->report->present($l)),
            'totals'         => [
                'gross'      => (int) $lines->sum('gross_paisa'),
                'vat'        => (int) $lines->sum('vat_paisa'),
                'commission' => (int) $lines->sum('commission_paisa'),
                'vendor'     => (int) $lines->sum('vendor_paisa'),
                'platform_service' => (int) $lines->sum('platform_service_paisa'),
            ],
        ]);
    }

    /**
     * A person has looked at a booking whose money is held back and lets it go. The note says why and is kept for good.
     * It covers that one reason only: if the booking changes in another way later, it is held again.
     */
    public function review(Request $request, int $number): JsonResponse
    {
        $this->adminOnly($request);

        $data = $request->validate(['note' => ['required', 'string', 'min:3', 'max:500']]);

        $this->ledger->run(2.0);
        $code = $this->report->problemCode($number);
        abort_if(!$code, 422, 'Nothing is held back for this booking that can be released here.');
        abort_if($code === 'manual_hold', 422, 'This booking is on hold by an admin. Lift the hold instead.');

        DB::table('money_reviews')->insert([
            'booking_number' => $number, 'reason' => $code, 'note' => trim($data['note']),
            'reviewed_by' => $request->user()->id, 'reviewed_by_name' => $request->user()->name,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->sendSuccess(['released' => true], 'Checked. The money for this booking is released.');
    }

    /** All ledger lines, newest first (the "see all payments" list) */
    public function lines(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $this->ledger->run(2.0);

        $request->validate([
            'type' => ['nullable', 'in:payment,refund'], 'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'between:5,100'],
        ]);

        $page = $this->report->lines($request->only(['vendor_id', 'booking_number', 'type', 'from', 'to', 'flagged', 'search']))
            ->paginate((int) $request->input('per_page', 25));
        $this->report->loadPaidOut($page->getCollection());

        return $this->sendSuccess([
            'lines' => $page->getCollection()->map(fn ($l) => $this->report->present($l))->values(),
            'meta'  => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()],
        ]);
    }

    // ────────────────────────────────────────────────────────

    /** @return array{0:?string,1:?string,2:string} from, to (Dhaka days) and a label */
    private function period(Request $request): array
    {
        $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d']]);

        if ($request->filled('from') || $request->filled('to')) {
            return [$request->input('from'), $request->input('to'), 'Custom dates'];
        }

        $now = CarbonImmutable::now(MoneyRules::TZ);

        return match ($request->input('period', 'week')) {
            'today'      => [$now->toDateString(), $now->toDateString(), 'Today'],
            'last_week'  => [$now->startOfWeek()->subWeek()->toDateString(), $now->startOfWeek()->subDay()->toDateString(), 'Last week'],
            'month'      => [$now->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString(), 'This month'],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth()->toDateString(), $now->subMonthNoOverflow()->endOfMonth()->toDateString(), 'Last month'],
            'all'        => [null, null, 'Everything so far'],
            default      => [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString(), 'This week'],
        };
    }

    private function settingsNote(): array
    {
        return [
            'commission_percent' => (float) $this->rules->get('commission_percent'),
            'wait_days'          => $this->rules->waitDays(),
            'confirmed'          => $this->rules->isConfirmed(),
            'start_date'         => $this->rules->startsAt()->toDateString(),
        ];
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can see the money pages.');
    }
}
