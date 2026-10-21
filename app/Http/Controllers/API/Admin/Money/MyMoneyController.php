<?php

namespace App\Http\Controllers\API\Admin\Money;

use App\Http\Controllers\BaseController;
use App\Services\Money\LedgerSync;
use App\Services\Money\StatementExport;
use App\Services\Money\VendorMoney;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "My money" for a vendor: what they have been paid, what comes next, and their own paid statements.
 * Always the signed-in user's OWN money: there is no vendor id to pass, so there is nobody else to ask about.
 * A statement that is not theirs, or is not paid, is "not found" (never "forbidden": that would confirm that it exists).
 */
class MyMoneyController extends BaseController
{
    public function __construct(private readonly VendorMoney $money, private readonly LedgerSync $ledger) {}

    public function summary(Request $request): JsonResponse
    {
        $this->ledger->run(2.0);   // pick up anything that came in since the last minute

        return $this->sendSuccess($this->money->summary($request->user()));
    }

    public function statements(Request $request): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1', 'max:100000']]);

        return $this->sendSuccess($this->money->statements($request->user(), (int) $request->input('page', 1)));
    }

    public function statement(Request $request, int $statement): JsonResponse
    {
        $s = $this->money->find($request->user(), $statement);
        abort_if($s === null, 404, 'That statement could not be found.');

        return $this->sendSuccess($this->money->statement($s));
    }

    public function csv(Request $request, int $statement): StreamedResponse
    {
        $s = $this->money->find($request->user(), $statement);
        abort_if($s === null, 404, 'That statement could not be found.');

        return app(StatementExport::class)->stream($s);
    }
}
