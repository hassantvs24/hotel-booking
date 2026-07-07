<?php

namespace App\Http\Controllers\API\Admin\Notification;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    /*
     * Fetch paginated notification for the authenticated admin
     * @return JsonResponse
     */
    public function index() : JsonResponse
    {
        $notifications = auth()->user()->notifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(fn($notification) => [
                'id'         => $notification->id,
                'type'       => $notification->data['type']    ?? 'info',
                'title'      => $notification->data['title']   ?? 'Notification',
                'message'    => $notification->data['message'] ?? '',
                'icon'       => $notification->data['icon']    ?? 'bx-bell',
                'color'      => $notification->data['color']   ?? 'teal',
                'read'       => !is_null($notification->read_at),
                'created_at' => $notification->created_at->toISOString(),
            ]);

        return $this->sendSuccess([
            'notifications' => $notifications,
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    /*
     * Mark a single notification as read for the authenticated admin
     * @param string $notification
     */

    public function markRead(string $id): JsonResponse
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();

        return $this->sendSuccess(null, 'Notification marked as read');
    }

    /*
     * Mark all notifications as read for the authenticated admin
     */
    public function markAllRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->markAsRead();
        return $this->sendSuccess(null, 'Notifications marked as read');
    }
}
