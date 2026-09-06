<?php

namespace App\Events\Booking;

use App\Models\RoomRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\BID\BidReceivedNotification;
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

    public function notifyAll(): void
    {
        $room     = $this->roomRequest->room;
        $property = $room?->property;
        $payload  = $this->broadcastWith();

        // Admin DB — deduped by room_request_id
        User::admins()->each(fn($admin) =>
        $admin->notify(new AdminNotification(
            type:    'bid',
            title:   $payload['title'],
            message: $payload['message'],
            icon:    'bx-tag',
            color:   'blue',
            extra:   ['room_request_id' => $this->roomRequest->id],
            channel: 'admin',
        ))
        );

        // Owner WebPush via BidReceivedNotification
        // Skip if owner is also an admin — they already got the admin DB notification
        if ($property?->user && $room) {
            if (!(bool) $property->user->is_admin) {
                $property->user->notify(
                    new BidReceivedNotification($this->roomRequest, $room, $this->roomRequest->user)
                );
            }
        }

        // Guest DB — confirmation bid was sent
        if ($this->roomRequest->user) {
            $this->roomRequest->user->notify(new AdminNotification(
                type:    'bid',
                title:   'Bid submitted',
                message: 'Your offer for ' . ($room?->name ?? 'the room') . ' was sent to the owner',
                icon:    'bx-tag',
                color:   'teal',
                extra:   ['room_request_id' => 'guest_' . $this->roomRequest->id],
                channel: 'user',
            ));
        }
    }
}
