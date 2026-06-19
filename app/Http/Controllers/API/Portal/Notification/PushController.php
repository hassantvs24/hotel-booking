<?php

namespace App\Http\Controllers\API\Portal\Notification;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NotificationChannels\WebPush\PushSubscription;

class PushController extends BaseController
{
    // GET /portal/push/vapid-key
    public function vapidKey(): JsonResponse
    {
        return $this->sendSuccess([
            'vapid_public_key' => config('webpush.vapid.public_key'),
        ]);
    }

    // POST /portal/push/subscribe
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'     => 'required|url',
            'keys.auth'    => 'required|string',
            'keys.p256dh'  => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth'],
        );

        return $this->sendSuccess(['message' => 'Subscribed to push notifications.']);
    }

    // DELETE /portal/push/unsubscribe
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required']);

        $request->user()->deletePushSubscription($request->endpoint);

        return $this->sendSuccess(['message' => 'Unsubscribed.']);
    }
}
