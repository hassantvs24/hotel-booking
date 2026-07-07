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
        $channels = [
            new PrivateChannel('admin.notifications'),
        ];

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

        return [
            'id'             => $this->booking->id,
            'type'           => 'booking',
            'title'          => 'New booking — ' . ($property?->name ?? 'property'),
            'message'        => '# ' . $this->booking->booking_number . ' by ' . ($this->booking->user?->name ?? 'Guest'),
            'icon'           => 'bx-calendar-check',
            'color'          => 'teal',
            'booking_number' => $this->booking->booking_number,
            'created_at'     => now()->toISOString(),
        ];
    }

    /**
     * Save DB notifications for all audiences.
     * Call this from the controller AFTER dispatching the event,
     * NOT inside broadcastWith() which runs on every Reverb serialization.
     *
     * Usage:
     *   $event = new BookingCreated($booking);
     *   event($event);
     *   $event->notifyAll();
     */
    public function notifyAll(): void
    {
        $property = $this->booking->room?->property;
        $propertyId = $this->booking->room?->property_id ?? 0;
        $payload = $this->broadcastWith();

        // Admin DB notification
        User::admins()->each(fn($admin) =>
        $admin->notify(new AdminNotification(
            type:    'booking',
            title:   $payload['title'],
            message: $payload['message'],
            icon:    'bx-calendar-check',
            color:   'teal',
            extra:   ['booking_number' => $this->booking->booking_number],
            channel: 'admin',
        ))
        );

        // Owner DB + WebPush
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
