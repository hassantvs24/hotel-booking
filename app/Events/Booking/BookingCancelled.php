<?php

namespace App\Events\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.notifications'),
            new PrivateChannel('App.Models.User.' . $this->booking->user_id),
        ];

        if ($this->booking->room?->property_id) {
            $channels[] = new PrivateChannel(
                'property.' . $this->booking->room->property_id . '.notifications'
            );
        }

        return $channels;
    }

    public function broadcastAs(): string { return 'booking.cancelled'; }

    // broadcastWith ONLY returns the payload — NO DB saves here
    public function broadcastWith(): array
    {
        return [
            'id'             => $this->booking->id,
            'type'           => 'booking',
            'title'          => 'Booking cancelled',
            'message'        => '# ' . $this->booking->booking_number . ' has been cancelled',
            'icon'           => 'bx-calendar-x',
            'color'          => 'red',
            'booking_number' => $this->booking->booking_number,
            'created_at'     => now()->toISOString(),
        ];
    }

    // Call this ONCE from the controller after event()
    public function notifyAll(): void
    {
        $property   = $this->booking->room?->property;
        $propertyId = $this->booking->room?->property_id ?? 0;

        // Admin DB
        User::admins()->each(fn($admin) =>
        $admin->notify(new AdminNotification(
            type:    'booking',
            title:   'Booking cancelled',
            message: '# ' . $this->booking->booking_number . ' has been cancelled',
            icon:    'bx-calendar-x',
            color:   'red',
            extra:   ['booking_number' => $this->booking->booking_number],
            channel: 'admin',
        ))
        );

        // Owner DB
        if ($property?->user) {
            $property->user->notify(new AdminNotification(
                type:    'booking',
                title:   'Booking cancelled — ' . ($property->name ?? ''),
                message: '# ' . $this->booking->booking_number . ' was cancelled, room is now available',
                icon:    'bx-calendar-x',
                color:   'red',
                extra:   ['booking_number' => $this->booking->booking_number],
                channel: 'admin',
            ));
        }

        // Guest DB
        if ($this->booking->user) {
            $this->booking->user->notify(new AdminNotification(
                type:    'booking',
                title:   'Booking cancelled',
                message: 'Your booking # ' . $this->booking->booking_number . ' has been cancelled',
                icon:    'bx-calendar-x',
                color:   'red',
                extra:   ['booking_number' => $this->booking->booking_number],
                channel: 'user',
            ));
        }
    }
}
