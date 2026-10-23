<?php

namespace App\Http\Controllers\API\Admin\Notification;

use App\Http\Controllers\BaseController;
use App\Services\Notify\NotificationPresenter;
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
            ->map(fn ($n) => NotificationPresenter::present($n));

        // how many are still unread in each kind (the Messages menu shows its own count from this)
        $byCategory = [];
        foreach ($user->unreadNotifications()->get(['data']) as $n) {
            $category = NotificationPresenter::categoryOf($n);
            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;
        }

        return $this->sendSuccess([
            'notifications'      => $notifications,
            'unread_count'       => array_sum($byCategory),
            'unread_by_category' => (object) $byCategory,
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
