<?php

namespace App\Events\Admin\Booking;

use App\Models\RoomRequest;
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
        return [new PrivateChannel('admin.notifications')];
    }

    public function broadcastAs(): string
    {
        return 'bid.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->roomRequest->id,
            'type'       => 'bid',
            'title'      => 'New bid submitted',
            'message'    => "Bid #{$this->roomRequest->bid_number} by {$this->roomRequest->user?->name}",
            'icon'       => 'bx-tag',
            'color'      => 'blue',
            'bid_number' => $this->roomRequest->bid_number,
            'created_at' => now()->toISOString(),
        ];
    }
}
