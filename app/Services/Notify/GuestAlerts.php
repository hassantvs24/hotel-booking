<?php

namespace App\Services\Notify;

use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;

/**
 * What a GUEST hears in the bell of the guest site, beside the offers (see BidAlerts): the hotel wrote to them, a refund was
 * approved, declined or paid, a stay starts tomorrow. The guest site shows the "body" and goes to the "url" of a line.
 *
 * Only a bell: every method swallows its own errors. A message, a refund or a booking is never held up, undone or made to
 * fail because a notification could not be made.
 */
class GuestAlerts
{
    /** The hotel (its owner, staff or the Oystay team) wrote to the guest in a conversation */
    public function hotelReplied(ChatMessage $message): void
    {
        $this->safely(function () use ($message) {
            if ($message->is_internal || $message->type !== 'text') {
                return;
            }

            $conversation = $message->conversation()->with('customer', 'property')->first();
            $guest = $conversation?->customer;

            // only the hotel writing to the guest is news for the guest
            if (!$guest || $guest->trashed() || (int) $message->sender_id === (int) $conversation->customer_id) {
                return;
            }

            $hotel = $conversation->property?->name ?: 'the hotel';
            $text = trim((string) $message->message);
            $snippet = $text !== '' ? Str::limit($text, 90) : 'Sent you a file';

            // lines the guest has not opened yet become one counted line ("3 new messages from X"), like the hotel's bell
            $earlier = $guest->unreadNotifications()->get()->filter(
                fn ($n) => ($n->data['type'] ?? null) === 'message' && (int) ($n->data['conversation_id'] ?? 0) === (int) $conversation->id
            );
            $count = $earlier->sum(fn ($n) => max(1, (int) ($n->data['count'] ?? 1))) + 1;
            $earlier->each->delete();

            $this->tell($guest, 'message', $count > 1 ? "{$count} new messages from {$hotel}" : "New message from {$hotel}", $snippet, 'bx-message-dots', 'teal',
                '/profile/messages?conversation=' . $conversation->id, ['conversation_id' => $conversation->id, 'property_id' => $conversation->property_id, 'count' => $count]);
        });
    }

    /** A refund the guest asked for was approved, declined or paid back (called when the refund's status changes) */
    public function refundChanged(RefundRequest $refund): void
    {
        $this->safely(function () use ($refund) {
            if (!in_array($refund->status, [RefundRequest::STATUS_APPROVED, RefundRequest::STATUS_REJECTED, RefundRequest::STATUS_COMPLETED], true)) {
                return;
            }

            $refund->loadMissing('booking.room.property', 'user');
            $guest = $refund->user;
            $booking = $refund->booking;

            if (!$guest || !$booking) {
                return;
            }

            // each answer once, even if the status is touched again
            $already = $guest->notifications()->where('created_at', '>=', now()->subDays(30))->get(['data'])
                ->contains(fn ($n) => ($n->data['type'] ?? null) === 'refund' && (int) ($n->data['refund_request_id'] ?? 0) === (int) $refund->id && ($n->data['status'] ?? null) === $refund->status);

            if ($already) {
                return;
            }

            $hotel = $booking->room?->property?->name;
            $for = "booking #{$booking->booking_number}" . ($hotel ? " ({$hotel})" : '');
            $money = BidMessages::money((float) ($refund->approved_amount ?? $refund->requested_amount));

            [$title, $body, $icon, $color] = match ($refund->status) {
                RefundRequest::STATUS_APPROVED  => ['Refund approved', "Your refund of {$money} for {$for} was approved. It is being sent back to your original payment method.", 'bx-check-circle', 'green'],
                // (the admin's note is internal and is never shown to the guest)
                RefundRequest::STATUS_REJECTED  => ['Refund request declined', "Your refund request for {$for} was not approved. You can message the hotel from My Messages if you have a question.", 'bx-x-circle', 'red'],
                default                         => ['Refund paid', "{$money} for {$for} was returned to your original payment method. It can take a few days to show in your account.", 'bx-undo', 'green'],
            };

            $this->tell($guest, 'refund', $title, $body, $icon, $color, '/profile/bookings',
                ['refund_request_id' => $refund->id, 'refund_number' => $refund->refund_number, 'booking_number' => $booking->booking_number, 'status' => $refund->status]);
        });
    }

    /** The guest's stay starts tomorrow (one line for the guest and hotel and day, even for a cart of several rooms) */
    public function arrivalReminder(Booking $booking): void
    {
        $this->safely(function () use ($booking) {
            $booking->loadMissing('room.property', 'user');
            $guest = $booking->user;
            $property = $booking->room?->property;

            if (!$guest || !$property) {
                return;
            }

            $day = Carbon::parse($booking->checkin)->toDateString();

            $already = $guest->notifications()->where('created_at', '>=', now()->subDays(3))->get(['data'])
                ->contains(fn ($n) => ($n->data['type'] ?? null) === 'reminder' && (int) ($n->data['property_id'] ?? 0) === (int) $property->id && ($n->data['checkin'] ?? null) === $day);

            if ($already) {
                return;
            }

            $nights = max(1, (int) round(Carbon::parse($booking->checkin)->startOfDay()->diffInDays(Carbon::parse($booking->checkout)->startOfDay())));

            $this->tell($guest, 'reminder', "Your stay at {$property->name} starts tomorrow",
                'Booking #' . $booking->booking_number . ' · ' . ($booking->room?->name ?: 'your room') . ' · ' . Carbon::parse($booking->checkin)->format('j M') . " ({$nights} " . ($nights === 1 ? 'night' : 'nights') . '). Have a lovely stay! You can message the hotel from My Messages if you need anything.',
                'bx-calendar-check', 'blue', '/profile/bookings', ['property_id' => $property->id, 'booking_number' => $booking->booking_number, 'checkin' => $day]);
        });
    }

    private function tell(User $guest, string $type, string $title, string $body, string $icon, string $color, string $url, array $extra): void
    {
        $guest->notify(new AdminNotification(
            type: $type, title: $title, message: $body, icon: $icon, color: $color,
            extra: $extra + ['body' => $body, 'url' => $url, 'always' => true], channel: 'user',
        ));
    }

    private function safely(callable $work): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
