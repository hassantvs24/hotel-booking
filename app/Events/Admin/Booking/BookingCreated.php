<?php

namespace App\Events\Admin\Booking;

use App\Models\Booking;
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
        return [new PrivateChannel('admin.notifications')];
    }

    public function broadcastAs(): string
    {
        return 'booking.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->booking->id,
            'type'           => 'booking',
            'title'          => 'New booking',
            'message'        => "#{$this->booking->booking_number} by {$this->booking->user?->name}",
            'icon'           => 'bx-calendar-check',
            'color'          => 'teal',
            'booking_number' => $this->booking->booking_number,
            'created_at'     => now()->toISOString(),
        ];
    }
}
