<?php

// Runs every minute via the scheduler.
// Books new payments and completed refunds into the money ledger. Safe to run as often as you like:
// a payment or refund that is already booked is skipped.

namespace App\Jobs;

use App\Services\Money\LedgerSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncLedger implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(LedgerSync $ledger): void
    {
        $added = $ledger->run();

        if ($added['payments'] || $added['refunds']) {
            Log::info("[SyncLedger] Booked {$added['payments']} payment(s) and {$added['refunds']} refund(s).");
        }
    }
}
