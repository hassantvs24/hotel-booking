<?php

namespace App\Events\Booking;

use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A guest asked for a refund — tell the admins and the property's owner (the bell in the admin panel).
 * Same shape as BookingCancelled: event() pushes it live, notifyAll() saves it so it is there on the next load.
 */
class RefundRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RefundRequest $refund) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.notifications')];

        $propertyId = $this->refund->booking?->room?->property_id;
        if ($propertyId) {
            $channels[] = new PrivateChannel('property.' . $propertyId . '.notifications');
        }

        return $channels;
    }

    public function broadcastAs(): string { return 'refund.requested'; }

    public function broadcastWith(): array
    {
        return array_merge($this->content(), [
            'id'         => 'refund_' . $this->refund->id,
            'created_at' => now()->toISOString(),
        ]);
    }

    /** Save it for every admin and for the property owner */
    public function notifyAll(): void
    {
        $content = $this->content();
        $extra   = [
            'refund_request_id' => $this->refund->id,
            'refund_number'     => $this->refund->refund_number,
            'booking_number'    => $this->refund->booking?->booking_number,
        ];

        $send = fn (User $user, string $title) => $user->notify(new AdminNotification(
            type:    'refund',
            title:   $title,
            message: $content['message'],
            icon:    $content['icon'],
            color:   $content['color'],
            extra:   $extra,
            channel: 'admin',
        ));

        User::admins()->each(fn (User $admin) => $send($admin, $content['title']));

        $owner = $this->refund->booking?->room?->property?->user;
        if ($owner && !$owner->is_admin) {
            $send($owner, $content['title'] . ' — ' . ($this->refund->booking->room->property->name ?? ''));
        }
    }

    private function content(): array
    {
        $this->refund->loadMissing(['booking.room.property.user', 'user']);

        $booking = $this->refund->booking;
        $guest   = $this->refund->user?->name ?: 'A guest';
        $amount  = (float) $this->refund->requested_amount;
        $money   = 'BDT ' . (abs($amount - round($amount)) < 0.005 ? number_format(round($amount)) : number_format($amount, 2));

        return [
            'type'           => 'refund',
            'title'          => 'Refund requested',
            'message'        => "{$guest} asked for a refund of {$money} · booking #{$booking?->booking_number}",
            'icon'           => 'bx-undo',
            'color'          => 'amber',
            'refund_number'  => $this->refund->refund_number,
            'booking_number' => $booking?->booking_number,
        ];
    }
}
