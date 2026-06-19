<?php

namespace App\Http\Controllers\API\Portal\Notification;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user          = $request->user();
        $notifications = $user->notifications()
            ->latest()
            ->take(50)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'data'       => $n->data,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return $this->sendSuccess([
            'data'          => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, $id): JsonResponse
    {
        $request->user()
            ->notifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return $this->sendSuccess(['message' => 'Marked as read.']);
    }


    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return $this->sendSuccess(['message' => 'All marked as read.']);
    }
}
