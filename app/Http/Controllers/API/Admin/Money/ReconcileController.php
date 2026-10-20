<?php

namespace App\Http\Controllers\API\Admin\Money;

use App\Http\Controllers\BaseController;
use App\Models\BankDeposit;
use App\Models\GatewayCheck;
use App\Services\Money\BankDepositService;
use App\Services\Money\GatewayChecker;
use App\Services\Money\LedgerSync;
use App\Services\Money\MoneyRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Reconciliation screens (platform admin only): does SSLCommerz agree with our records, and did the bank receive what it should.
 * This only reports: nothing here changes a booking, a payment or the money ledger.
 */
class ReconcileController extends BaseController
{
    public function __construct(private readonly GatewayChecker $checker, private readonly BankDepositService $deposits, private readonly LedgerSync $ledger) {}

    public function index(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $this->ledger->run(2.0);   // pick up anything that came in since the last minute

        return $this->sendSuccess($this->payload());
    }

    /** What should have arrived for a period (the form shows it while the admin types the dates) */
    public function expected(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $this->ledger->run(2.0);

        return $this->sendSuccess($this->deposits->expected($d['from'], $d['to']));
    }

    /** Ask SSLCommerz now (instead of waiting for the next automatic run) */
    public function run(Request $request): JsonResponse
    {
        $this->adminOnly($request);

        $result = $this->checker->run(30, 10.0, true);   // (10 s of asking + at most one answer of 10 s: well inside a web request's limit)

        return $this->sendSuccess(['run' => $result] + $this->payload(), $result['checked'] === 0 ? 'Nothing was due to be checked right now. Everything is up to date.' : "Asked SSLCommerz about {$result['checked']} payment(s).");
    }

    /** The admin has looked at a payment that did not agree, and says what they found */
    public function resolve(Request $request, GatewayCheck $check): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['note' => ['required', 'string', 'min:3', 'max:500']]);

        abort_if($check->result === GatewayCheck::OK, 422, 'This payment agrees with SSLCommerz: there is nothing to resolve.');
        abort_if($check->resolved_at !== null, 422, 'This was already looked at' . ($check->resolved_by_name ? ' by ' . $check->resolved_by_name : '') . '. The first note stays on record.');

        $done = GatewayCheck::where('id', $check->id)->whereNull('resolved_at')->where('result', '!=', GatewayCheck::OK)
            ->update(['resolved_at' => now(), 'resolved_by' => $request->user()->id, 'resolved_by_name' => $request->user()->name, 'resolved_note' => trim($d['note'])]);

        abort_if($done === 0, 422, 'This was already looked at, or it agrees with SSLCommerz now. Reload the page.');

        return $this->sendSuccess($this->payload(), 'Noted.');
    }

    public function record(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate([
            'period_from' => ['required', 'date_format:Y-m-d'], 'period_to' => ['required', 'date_format:Y-m-d'], 'received_on' => ['required', 'date_format:Y-m-d'],
            'received_taka' => ['required', 'string', 'max:30'], 'reference' => ['nullable', 'string', 'max:120'], 'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->ledger->run(2.0);   // the expected amount is worked out from the books: they must be up to date
        $this->deposits->record($request->user(), $d);

        return $this->sendSuccess($this->payload(), 'Bank deposit written down.');
    }

    public function void(Request $request, BankDeposit $deposit): JsonResponse
    {
        $this->adminOnly($request);
        $d = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        $this->deposits->void($deposit, $request->user(), $d['reason']);

        return $this->sendSuccess($this->payload(), 'The deposit was voided.');
    }

    // ────────────────────────────────────────────────────────

    /** The whole screen */
    public function payload(): array
    {
        $open = GatewayCheck::needingLook()->orderBy('checked_at')->limit(100)->get();
        $last = DB::table('gateway_check_runs')->whereNotNull('finished_at')->orderByDesc('id')->first();
        $next = $this->deposits->next();

        return [
            'gateway' => [
                'last_run_at'   => MoneyRules::local($last?->finished_at),
                'stale'         => self::checkIsStale(),
                'last_auto_at'  => MoneyRules::local(DB::table('gateway_check_runs')->where('auto', true)->whereNotNull('finished_at')->max('finished_at')),
                'last_run'      => $last ? ['checked' => $last->checked, 'agreed' => $last->agreed, 'problems' => $last->problems, 'errors' => $last->errors] : null,
                'total'         => GatewayCheck::count(),
                'agreeing'      => GatewayCheck::where('result', GatewayCheck::OK)->count(),
                'open'          => GatewayCheck::needingLook()->count(),   // (the list below shows the first 100)
                'resolved'      => GatewayCheck::where('result', '!=', GatewayCheck::OK)->whereNotNull('resolved_at')->count(),
                'unreachable'   => GatewayCheck::where('attempts', '>', 0)->where('attempts', '<', 6)->count(),
                'configured'    => (bool) config('sslcommerz.apiCredentials.store_id') && (bool) config('sslcommerz.apiCredentials.store_password'),
                'problems'      => $open->map(fn (GatewayCheck $c) => $this->problem($c))->values(),
            ],
            'bank' => [
                'check_days'    => $this->deposits->checkDays(),
                'next'          => $next + ['ready' => $next['to'] >= $next['from'], 'expected' => $next['to'] >= $next['from'] ? $this->deposits->expected($next['from'], $next['to']) : null],
                'awaiting_paisa' => $this->deposits->awaitingBank(),
                'deposits'      => BankDeposit::orderByDesc('period_to')->orderByDesc('id')->limit(50)->get()->map(fn (BankDeposit $b) => $this->deposit($b))->values(),
            ],
        ];
    }

    /** A short summary for the Money overview and the Payouts page */
    public static function summary(): array
    {
        $deposits = app(BankDepositService::class);
        $next     = $deposits->next();

        return [
            'open_problems'  => GatewayCheck::needingLook()->count(),
            'check_stale'    => self::checkIsStale(),
            'bank_overdue'   => $next['overdue'] && $next['to'] >= $next['from'],
            'bank_due_on'    => $next['due_on'],
            'awaiting_paisa' => $deposits->awaitingBank(),
            'last_deposit_to' => $deposits->last()?->period_to?->toDateString(),
        ];
    }

    /** The scheduler's own run is written every 10 minutes (even when nothing is due). None for 45 minutes: the server's scheduler is not running. A run started with "Check now" does not count. */
    public static function checkIsStale(): bool
    {
        $last = DB::table('gateway_check_runs')->where('auto', true)->whereNotNull('finished_at')->max('finished_at');

        return !$last || CarbonImmutable::parse($last, 'UTC')->lt(now()->subMinutes(45));
    }

    private function problem(GatewayCheck $c): array
    {
        return [
            'id' => $c->id, 'tran_id' => $c->tran_id, 'kind' => $c->kind, 'result' => $c->result, 'problem' => $c->problem, 'bookings' => $c->bookings ?? [],
            'our_status' => $c->our_status, 'our_paisa' => (int) $c->our_paisa, 'gateway_status' => $c->gateway_status, 'gateway_paisa' => $c->gateway_paisa === null ? null : (int) $c->gateway_paisa,
            'bank_tran_id' => $c->bank_tran_id, 'checked_at' => MoneyRules::local($c->checked_at),
        ];
    }

    private function deposit(BankDeposit $b): array
    {
        return [
            'id' => $b->id, 'period_from' => $b->period_from->toDateString(), 'period_to' => $b->period_to->toDateString(), 'received_on' => $b->received_on->toDateString(),
            'received_paisa' => (int) $b->received_paisa, 'expected_paisa' => (int) $b->expected_paisa, 'difference_paisa' => (int) $b->difference_paisa,
            'fee_unknown_count' => (int) $b->fee_unknown_count, 'reference' => $b->reference, 'note' => $b->note, 'entered_by_name' => $b->entered_by_name,
            'entered_at' => MoneyRules::local($b->created_at), 'voided' => $b->isVoided(), 'voided_by_name' => $b->voided_by_name, 'void_reason' => $b->void_reason,
        ];
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can see or change the reconciliation.');
    }
}
