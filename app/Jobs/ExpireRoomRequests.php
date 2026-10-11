<?php

// Runs every 5 minutes via scheduler.
// Marks bids whose response/payment window has lapsed as Timeout.
// The 3-bid limit already ignores lapsed bids (RoomRequest::scopeActive),
// so this job is about keeping the stored status honest for admin/owner views.

namespace App\Jobs;

use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireRoomRequests implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        $ids = RoomRequest::expired()->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $expired = DB::transaction(function () use ($ids) {
            RoomRequestAccepted::whereIn('room_requests_id', $ids)->delete();

            return RoomRequest::whereIn('id', $ids)
                ->whereIn('status', RoomRequest::LIVE_STATUSES)
                ->update(['status' => 'Timeout']);
        });

        Log::info("[ExpireRoomRequests] Timed out {$expired} bid(s).");
    }
}
