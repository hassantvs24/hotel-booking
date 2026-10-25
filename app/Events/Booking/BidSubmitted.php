<?php

namespace App\Events\Booking;

use App\Models\RoomRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RoomRequest $roomRequest) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.notifications')];

        if ($this->roomRequest->property_id) {
            $channels[] = new PrivateChannel(
                'property.' . $this->roomRequest->property_id . '.notifications'
            );
        }

        return $channels;
    }

    public function broadcastAs(): string { return 'bid.submitted'; }

    public function broadcastWith(): array
    {
        $room     = $this->roomRequest->room;
        $property = $room?->property;

        return [
            'id'              => $this->roomRequest->id,
            'type'            => 'bid',
            'title'           => 'New bid — ' . ($property?->name ?? 'property') . ' · ' . ($room?->name ?? 'room'),
            'message'         => ($this->roomRequest->user?->name ?? 'Guest') . ' offered BDT ' . number_format($this->roomRequest->discount_price) . '/night',
            'icon'            => 'bx-tag',
            'color'           => 'blue',
            'bid_number'      => $this->roomRequest->bid_number,
            'room_request_id' => $this->roomRequest->id,
            'created_at'      => now()->toISOString(),
        ];
    }

    /** Tells the guest, the hotel's people and the admin (see BidAlerts: it never lets a failed bell hold anything up) */
    public function notifyAll(): void
    {
        app(\App\Services\Notify\BidAlerts::class)->submitted($this->roomRequest);
    }
}
