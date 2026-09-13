<?php

namespace App\Events\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\Booking\PaymentConfirmedNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.notifications')];

        if ($this->booking->room?->property_id) {
            $channels[] = new PrivateChannel(
                'property.' . $this->booking->room->property_id . '.notifications'
            );
        }

        return $channels;
    }

    public function broadcastAs(): string { return 'booking.created'; }

    public function broadcastWith(): array
    {
        $property = $this->booking->room?->property;
        $groupRef = $this->booking->booking_group_id
            ? optional($this->booking->group)->group_ref
            : null;
        $isGroup  = !is_null($groupRef);

        $title   = $isGroup
            ? 'Group booking — ' . ($property?->name ?? 'property')
            : 'New booking — '   . ($property?->name ?? 'property');

        $message = $isGroup
            ? 'Ref: ' . $groupRef . ' · by ' . ($this->booking->user?->name ?? 'Guest')
            : '# '   . $this->booking->booking_number . ' by ' . ($this->booking->user?->name ?? 'Guest');

        return [
            'id'             => $this->booking->id,
            'type'           => 'booking',
            'title'          => $title,
            'message'        => $message,
            'icon'           => 'bx-calendar-check',
            'color'          => 'teal',
            'booking_number' => $this->booking->booking_number,
            'group_ref'      => $groupRef,
            'created_at'     => now()->toISOString(),
        ];
    }

    /**
     * Call ONCE from the controller after event().
     * shouldSend() in AdminNotification prevents duplicate DB saves
     * by checking booking_number — safe to call even if accidentally invoked twice.
     */
    public function notifyAll(): void
    {
        $property   = $this->booking->room?->property;
        $payload    = $this->broadcastWith();

        // Admin DB — deduped by booking_number
        User::admins()->each(fn($admin) =>
        $admin->notify(new AdminNotification(
            type:    'booking',
            title:   $payload['title'],
            message: $payload['message'],
            icon:    'bx-calendar-check',
            color:   'teal',
            extra:   [
                'booking_number' => $this->booking->booking_number,
                'group_ref'      => $payload['group_ref'],
            ],
            channel: 'admin',
        ))
        );

        // Owner DB + WebPush — deduped internally by PaymentConfirmedNotification
        if ($property?->user) {
            $property->user->notify(
                new PaymentConfirmedNotification($this->booking, 'owner')
            );
        }

        // Guest DB + WebPush
        if ($this->booking->user) {
            $this->booking->user->notify(
                new PaymentConfirmedNotification($this->booking, 'guest')
            );
        }
    }
}
