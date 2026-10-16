<?php

// Runs every 5 minutes via scheduler.
// Marks bids whose response/payment window has lapsed as Timeout.
// The 3-bid limit already ignores lapsed bids (RoomRequest::scopeActive),
// so this job is about keeping the stored status honest for admin/owner views.

namespace App\Jobs;

use App\Models\RoomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ExpireRoomRequests implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        $expired = RoomRequest::expireLapsed();

        if ($expired > 0) {
            Log::info("[ExpireRoomRequests] Timed out {$expired} bid(s).");
        }
    }
}
