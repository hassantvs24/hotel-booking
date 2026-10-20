<?php

namespace App\Http\Controllers\API\Admin\Money;

use App\Http\Controllers\BaseController;
use App\Models\LedgerEntry;
use App\Models\PayoutStatement;
use App\Models\VendorPayoutAccount;
use App\Services\Money\LedgerSync;
use App\Services\Money\MoneyReport;
use App\Services\Money\MoneyRules;
use App\Services\Money\PayoutService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The admin's payout screens: what is ready to pay, the statements, marking one paid, reversing it, holding a booking.
 * Platform admin only. All amounts are whole paisa.
 */
class PayoutController extends BaseController
{
    public function __construct(
        private readonly PayoutService $payouts,
        private readonly MoneyReport $report,
        private readonly MoneyRules $rules,
        private readonly LedgerSync $ledger,
    ) {}

    /** Ready to pay (not in a statement yet) and the statements so far */
    public function index(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $this->ledger->run(2.0);

        $request->validate(['status' => ['nullable', 'in:draft,paid,reversed,cancelled'], 'vendor_id' => ['nullable', 'integer'], 'per_page' => ['nullable', 'integer', 'between:5,100']]);

        $statements = PayoutStatement::query()->orderByDesc('id')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', (int) $request->input('vendor_id')))
            ->paginate((int) $request->input('per_page', 20));

        $sums = PayoutStatement::query()->selectRaw("coalesce(sum(case when status = 'paid' then vendor_paisa end), 0) as paid, coalesce(sum(case when status = 'draft' then vendor_paisa end), 0) as draft, sum(status = 'draft') as drafts")->first();

        return $this->sendSuccess([
            'ready'      => $this->payouts->candidates(),
            'minimum_paisa' => $this->payouts->minimumPaisa(),
            'statements' => $statements->getCollection()->map(fn ($s) => $this->summary($s))->values(),
            'meta'       => ['page' => $statements->currentPage(), 'last_page' => $statements->lastPage(), 'total' => $statements->total(), 'per_page' => $statements->perPage()],
            'totals'     => ['paid_paisa' => (int) $sums->paid, 'in_drafts_paisa' => (int) $sums->draft, 'drafts' => (int) $sums->drafts],
            'schedule'   => $this->schedule(),
            'bank'       => ReconcileController::summary(),
            'problems'   => $this->payouts->problems(),
        ]);
    }

    /** Prepare draft statements, for every vendor with enough ready money, or for one vendor */
    public function generate(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $data = $request->validate(['vendor_id' => ['nullable', 'integer']]);

        $this->ledger->run(2.0);
        $created = $this->payouts->generate($request->user(), $data['vendor_id'] ?? null);

        return $this->sendSuccess(
            ['created' => $created->map(fn ($s) => $this->summary($s))->values()],
            $created->isEmpty() ? 'Nothing is ready to pay right now.' : $created->count() . ' statement(s) prepared. Check them, then send the money and mark them paid.'
        );
    }

    public function show(Request $request, PayoutStatement $statement): JsonResponse
    {
        $this->adminOnly($request);

        return $this->sendSuccess($this->detail($statement));
    }

    public function hold(Request $request, PayoutStatement $statement): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['booking_number' => ['required', 'integer'], 'note' => ['required', 'string', 'min:3', 'max:500']]);

        $this->payouts->holdBooking($statement, (int) $d['booking_number'], trim($d['note']), $request->user());

        return $this->sendSuccess($this->detail($statement->fresh()), 'The booking is out of this statement and its money is on hold.');
    }

    public function cancel(Request $request, PayoutStatement $statement): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $this->payouts->cancelDraft($statement, $request->user(), trim((string) ($d['note'] ?? '')) ?: 'Cancelled by the admin.');

        return $this->sendSuccess($this->detail($statement->fresh()), 'The draft statement was cancelled. Its bookings are ready to pay again.');
    }

    public function paid(Request $request, PayoutStatement $statement): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate([
            'method' => ['required', 'in:' . implode(',', PayoutStatement::METHODS)],
            'reference' => ['required', 'string', 'min:3', 'max:120'],
            'note' => ['nullable', 'string', 'max:500'],
            'confirm_account' => ['nullable', 'boolean'],
        ]);

        $this->ledger->run(2.0);
        $paid = $this->payouts->markPaid($statement, $request->user(), $d);

        return $this->sendSuccess($this->detail($paid), $paid->emailed_at
            ? 'Marked as paid. The statement is now locked and the vendor has been emailed.'
            : 'Marked as paid. The statement is now locked. The vendor could not be emailed (see the history), so tell them yourself.');
    }

    public function reverse(Request $request, PayoutStatement $statement): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        $this->payouts->reverse($statement, $request->user(), $d['reason']);

        return $this->sendSuccess($this->detail($statement->fresh()), 'The payment was reversed. Its bookings are ready to pay again.');
    }

    /** The statement as a spreadsheet (opens in Excel) */
    public function csv(Request $request, PayoutStatement $statement): StreamedResponse
    {
        $this->adminOnly($request);

        $rows = $statement->lines()->where(fn ($q) => $statement->isDraft() || $statement->isPaid() ? $q->where('active', 1) : $q)->orderBy('booking_number')->orderBy('id')->get();
        $taka = fn (int $p) => ($p < 0 ? '-' : '') . intdiv(abs($p), 100) . '.' . str_pad((string) (abs($p) % 100), 2, '0', STR_PAD_LEFT);
        // a cell that starts with = + - @ would be run as a formula by a spreadsheet: make it plain text
        $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($statement, $rows, $taka, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Statement', $statement->number, 'Vendor', $safe((string) $statement->vendor_name), 'Status', $statement->status, 'Paid on', $statement->paid_at?->timezone(MoneyRules::TZ)->toDateString(), 'Reference', $safe((string) $statement->reference)]);
            fputcsv($out, []);
            fputcsv($out, ['Booking', 'Property', 'Check-out', 'Type', 'Paid by guest (BDT)', 'VAT (BDT)', 'Service charge kept by Oystay (BDT)', 'Commission (BDT)', 'To vendor (BDT)']);

            foreach ($rows as $l) {
                fputcsv($out, [$l->booking_number, $safe((string) $l->property_name), $l->checkout_date?->toDateString(), $l->type, $taka($l->gross_paisa), $taka($l->vat_paisa), $taka($l->platform_service_paisa), $taka($l->commission_paisa), $taka($l->vendor_paisa)]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Total', '', '', '', $taka((int) $statement->gross_paisa), $taka((int) $statement->vat_paisa), $taka((int) $statement->platform_service_paisa), $taka((int) $statement->commission_paisa), $taka((int) $statement->vendor_paisa)]);
            fclose($out);
        }, ($statement->number ?: 'statement') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Hold the vendor's money for one booking (outside a statement too) */
    public function holdBooking(Request $request, int $number): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['note' => ['required', 'string', 'min:3', 'max:500']]);

        $this->payouts->hold($number, trim($d['note']), $request->user());

        return $this->sendSuccess(['held' => true], 'The money for this booking is on hold.');
    }

    public function unholdBooking(Request $request, int $number): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['note' => ['required', 'string', 'min:3', 'max:500']]);

        $this->payouts->unhold($number, trim($d['note']), $request->user());

        return $this->sendSuccess(['held' => false], 'The hold was lifted.');
    }

    // ────────────────────────────────────────────────────────

    private function summary(PayoutStatement $s): array
    {
        return [
            'id' => $s->id, 'number' => $s->number, 'vendor_id' => $s->vendor_id, 'vendor_name' => $s->vendor_name, 'status' => $s->status,
            'bookings_count' => $s->bookings_count, 'vendor_paisa' => (int) $s->vendor_paisa,
            'created_at' => MoneyRules::local($s->created_at), 'paid_at' => MoneyRules::local($s->paid_at), 'method' => $s->method, 'reference' => $s->reference,
        ];
    }

    private function detail(PayoutStatement $s): array
    {
        $this->report->forget();
        $live = in_array($s->status, [PayoutStatement::DRAFT, PayoutStatement::PAID], true);
        $lines = $s->lines()->where(fn ($q) => $live ? $q->where('active', 1) : $q)->orderBy('booking_number')->orderBy('id')->get();

        $notReady = $s->isDraft()
            ? LedgerEntry::whereIn('id', $lines->pluck('ledger_entry_id'))->whereRaw('not ' . $this->report->readySql())->pluck('booking_number')->unique()->map(fn ($n) => (int) $n)->values()->all()
            : [];
        $holds = DB::table('money_holds')->whereIn('booking_number', $lines->pluck('booking_number')->filter())->get()->keyBy('booking_number');

        $bookings = $lines->groupBy('booking_number')->map(function ($g, $number) use ($notReady, $holds) {
            $f = $g->first();

            return [
                'booking_number' => $number ?: null, 'property_name' => $f->property_name, 'checkout_date' => $f->checkout_date?->toDateString(),
                'gross_paisa' => (int) $g->sum('gross_paisa'), 'vat_paisa' => (int) $g->sum('vat_paisa'), 'commission_paisa' => (int) $g->sum('commission_paisa'),
                'platform_service_paisa' => (int) $g->sum('platform_service_paisa'),
                'vendor_paisa' => (int) $g->sum('vendor_paisa'), 'lines' => $g->count(),
                'no_longer_ready' => in_array((int) $number, $notReady, true), 'hold' => ($h = $holds[$number] ?? null) ? $h->note : null,
            ];
        })->values();

        $account = VendorPayoutAccount::where('user_id', $s->vendor_id)->first();
        $changes = DB::table('vendor_payout_account_changes')->where('user_id', $s->vendor_id)->orderByDesc('id')->limit(5)->get(['changed_by_name', 'created_at'])->map(fn ($c) => ['changed_by_name' => $c->changed_by_name, 'created_at' => MoneyRules::local($c->created_at)]);
        $accountChanged = $account && DB::table('vendor_payout_account_changes')->where('user_id', $s->vendor_id)->count() > 1
            && ($account->updated_at->gt($s->created_at) || $account->updated_at->gt(now()->subDays(3)));

        $problems = [];
        if ($s->isDraft()) {
            if (!$account) { $problems[] = 'This vendor has not given a payout account yet. Ask them to add it under Property settings → Payout account.'; }
            if ($accountChanged) { $problems[] = 'The vendor changed their payout account recently. Check with them before you send money.'; }
            if ($notReady) { $problems[] = 'Some bookings changed or are on hold since this statement was prepared. Take them out first.'; }
            if ((int) $s->vendor_paisa < $this->payouts->minimumPaisa()) { $problems[] = 'This is below the minimum payout.'; }
        }

        return array_merge($this->summary($s), [
            'payable_until' => $s->payable_until?->toDateString(),
            'lines_count' => $s->lines_count,
            'gross_paisa' => (int) $s->gross_paisa, 'vat_paisa' => (int) $s->vat_paisa, 'platform_service_paisa' => (int) $s->platform_service_paisa,
            'commission_paisa' => (int) $s->commission_paisa,
            'created_by_name' => $s->created_by_name, 'paid_by_name' => $s->paid_by_name, 'note' => $s->note,
            'emailed_at' => MoneyRules::local($s->emailed_at), 'account_recently_changed' => $s->account_recently_changed,
            'reversed_at' => MoneyRules::local($s->reversed_at), 'reversed_by_name' => $s->reversed_by_name, 'reverse_reason' => $s->reverse_reason,
            'bookings' => $bookings,
            'account' => $s->isPaid() || $s->status === PayoutStatement::REVERSED ? ($s->account_snapshot ?? $account?->details()) : $account?->details(),
            'account_changed' => (bool) $accountChanged,
            'account_changes' => $changes,
            'problems' => $problems,
            'can_pay' => $s->isDraft() && !$notReady && (int) $s->vendor_paisa > 0,
            'events' => DB::table('payout_events')->where('statement_id', $s->id)->orderBy('id')->get(['action', 'note', 'by_name', 'created_at'])->map(fn ($e) => ['action' => $e->action, 'note' => $e->note, 'by_name' => $e->by_name, 'created_at' => MoneyRules::local($e->created_at)]),
        ]);
    }

    /** When the next payout is due, from the settings (a reminder only: the admin decides when to pay) */
    private function schedule(): array
    {
        $freq = (string) $this->rules->get('payout_frequency');
        $day  = max(1, min(7, (int) $this->rules->get('payout_weekday')));
        $now  = CarbonImmutable::now(MoneyRules::TZ)->startOfDay();

        $next = match ($freq) {
            'daily'   => $now,
            'monthly' => $now->day === 1 ? $now : $now->addMonthNoOverflow()->startOfMonth(),
            default   => $now->dayOfWeekIso === $day ? $now : $now->next($day === 7 ? 0 : $day),
        };

        return ['frequency' => $freq, 'weekday' => $day, 'next_payout_on' => $next->toDateString(), 'today' => $now->toDateString()];
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can see or change payouts.');
    }
}
