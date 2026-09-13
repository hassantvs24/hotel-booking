<?php

namespace App\Http\Controllers\API\Admin\Notification;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;

class NotificationController extends BaseController
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type']    ?? 'info',
                'title'      => $n->data['title']   ?? 'Notification',
                'message'    => $n->data['message'] ?? '',
                'icon'       => $n->data['icon']    ?? 'bx-bell',
                'color'      => $n->data['color']   ?? 'teal',
                'read'       => !is_null($n->read_at),
                'created_at' => $n->created_at->toISOString(),
            ]);

        return $this->sendSuccess([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(), // () is fine for count()
        ]);
    }

    public function markRead(string $id): JsonResponse
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();

        return $this->sendSuccess(null, 'Notification marked as read');
    }

    public function markAllRead(): JsonResponse
    {
        // Must use magic property (no parentheses) to get collection
        // then call markAsRead() on the collection, not the relation
        auth()->user()->unreadNotifications->markAsRead();

        return $this->sendSuccess(null, 'All notifications marked as read');
    }
}
