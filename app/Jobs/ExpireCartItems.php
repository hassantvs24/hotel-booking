<?php

// app/Jobs/ExpireCartItems.php
// Runs every 5 minutes via scheduler.
// Cleans expired direct cart items globally —
// not just when the cart page is opened.

namespace App\Jobs;

use App\Models\BookingCart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ExpireCartItems implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        $deleted = BookingCart::where('is_bid', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        if ($deleted > 0) {
            Log::info("[ExpireCartItems] Removed {$deleted} expired cart items.");
        }
    }
}
