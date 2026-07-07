<?php

namespace App\Events\Admin\Booking;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.notifications')];
    }

    public function broadcastAs(): string
    {
        return 'payment.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->booking->id,
            'type'           => 'payment',
            'title'          => 'Payment received',
            'message'        => "BDT {$this->booking->transaction?->amount} for #{$this->booking->booking_number}",
            'icon'           => 'bx-credit-card',
            'color'          => 'green',
            'booking_number' => $this->booking->booking_number,
            'amount'         => $this->booking->transaction?->amount,
            'created_at'     => now()->toISOString(),
        ];
    }
}
