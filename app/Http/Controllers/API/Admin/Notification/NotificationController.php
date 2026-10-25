<?php

namespace App\Http\Controllers\API\Admin\Notification;

use App\Http\Controllers\BaseController;
use App\Services\Notify\NotificationCategories;
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
        $bySubkind = [];
        foreach ($user->unreadNotifications()->get(['data']) as $n) {
            $category = NotificationPresenter::categoryOf($n);
            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;

            if ($sub = NotificationPresenter::subkindOf((array) $n->data)) {
                $bySubkind[$sub] = ($bySubkind[$sub] ?? 0) + 1;
            }
        }

        return $this->sendSuccess([
            'notifications'      => $notifications,
            'unread_count'       => array_sum($byCategory),
            // (the pages of the property approvals, name changes and reports each show their own number: not part of the total)
            'unread_by_category' => (object) ($byCategory + $bySubkind),
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

    /** The person opened the page of one kind of notification (their menu shows a number there): all of that kind are read */
    public function markCategoryRead(string $category): JsonResponse
    {
        $isSub = in_array($category, NotificationPresenter::SUBKINDS, true);

        if (!$isSub && !array_key_exists($category, NotificationCategories::ALL)) {
            return $this->sendError('Unknown kind of notification.', [], 422);
        }

        auth()->user()->unreadNotifications->filter(fn ($n) => $isSub
            ? NotificationPresenter::subkindOf((array) $n->data) === $category
            : NotificationPresenter::categoryOf($n) === $category)->each->markAsRead();

        return $this->sendSuccess(null, 'Marked as read');
    }

    public function markAllRead(): JsonResponse
    {
        // Must use magic property (no parentheses) to get collection
        // then call markAsRead() on the collection, not the relation
        auth()->user()->unreadNotifications->markAsRead();

        return $this->sendSuccess(null, 'All notifications marked as read');
    }
}
