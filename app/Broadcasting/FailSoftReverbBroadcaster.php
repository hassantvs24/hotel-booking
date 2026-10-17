<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Support\Facades\Log;

/**
 * Reverb broadcaster that never breaks the request that triggered it.
 *
 * The live push (toasts, chat, bell) is an extra on top of data that is already
 * saved. If the websocket server is down, the default broadcaster throws and the
 * guest sees "Pusher error: cURL error 7" on actions like Send Offer — even though
 * the work itself is fine. Here the failure is logged and the request carries on.
 */
class FailSoftReverbBroadcaster extends PusherBroadcaster
{
    public function broadcast(array $channels, $event, array $payload = [])
    {
        try {
            parent::broadcast($channels, $event, $payload);
        } catch (\Throwable $e) {
            Log::warning("Broadcast '{$event}' skipped (is Reverb running?): " . $e->getMessage());
        }
    }
}
