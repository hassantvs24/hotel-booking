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

class PaymentReceived implements ShouldBroadcastNow
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

    public function broadcastAs(): string { return 'payment.received'; }

    // broadcastWith ONLY returns payload — NO DB saves here
    public function broadcastWith(): array
    {
        $property = $this->booking->room?->property;

        return [
            'id'             => $this->booking->id,
            'type'           => 'payment',
            'title'          => 'Payment received — ' . ($property?->name ?? 'property'),
            'message'        => 'BDT ' . number_format($this->booking->transaction?->amount ?? $this->booking->amount) . ' · # ' . $this->booking->booking_number,
            'icon'           => 'bx-credit-card',
            'color'          => 'green',
            'booking_number' => $this->booking->booking_number,
            'amount'         => $this->booking->transaction?->amount ?? $this->booking->amount,
            'created_at'     => now()->toISOString(),
        ];
    }

    // Call this ONCE from the controller after event()
    public function notifyAll(): void
    {
        $payload = $this->broadcastWith();

        // Admin DB
        User::admins()->each(fn($admin) =>
        $admin->notify(new AdminNotification(
            type:    'payment',
            title:   $payload['title'],
            message: $payload['message'],
            icon:    'bx-credit-card',
            color:   'green',
            extra:   ['booking_number' => $this->booking->booking_number],
            channel: 'admin',
        ))
        );
    }
}
