<?php

namespace App\Events\Booking;

use App\Models\RoomRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\BID\BidStatusNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RoomRequest $roomRequest) {}

    public function broadcastOn(): array
    {
        $channels = [
            // Admin sees all bid status changes
            new PrivateChannel('admin.notifications'),
            // Guest gets notified on their private channel
            new PrivateChannel('App.Models.User.' . $this->roomRequest->user_id),
        ];

        if ($this->roomRequest->property_id) {
            $channels[] = new PrivateChannel(
                'property.' . $this->roomRequest->property_id . '.notifications'
            );
        }

        return $channels;
    }

    public function broadcastAs(): string { return 'bid.status.updated'; }

    public function broadcastWith(): array
    {
        $status = $this->roomRequest->status;
        $room   = $this->roomRequest->room;

        return [
            'id'              => $this->roomRequest->id,
            'type'            => 'bid',
            'title'           => 'Bid ' . $status,
            'message'         => 'Bid # ' . $this->roomRequest->bid_number . ' · ' . ($room?->name ?? 'room') . ' is now ' . $status,
            'icon'            => match($status) {
                'Approved' => 'bx-check-circle',
                'Declined' => 'bx-x-circle',
                'Counter'  => 'bx-transfer',
                'Done'     => 'bx-badge-check',
                default    => 'bx-tag',
            },
            'color'           => match($status) {
                'Approved' => 'green',
                'Declined' => 'red',
                'Counter'  => 'blue',
                'Done'     => 'teal',
                default    => 'amber',
            },
            'bid_number'      => $this->roomRequest->bid_number,
            'room_request_id' => $this->roomRequest->id,
            'status'          => $status,
            'counter_price'   => $this->roomRequest->counter_price,
            'created_at'      => now()->toISOString(),
        ];
    }

    /**
     * Call ONCE from the controller after the status is saved. Tells the guest, the hotel's people and the admin,
     * except the person who did it (see BidAlerts: it never lets a failed bell hold anything up). "Done" is settled
     * by a paid booking, which has its own notifications.
     */
    public function notifyAll(): void
    {
        $event = match ($this->roomRequest->status) {
            'Approved' => 'accepted',
            'Counter'  => 'countered',
            'Declined' => 'declined',
            default    => null,
        };

        if ($event) {
            app(\App\Services\Notify\BidAlerts::class)->answered($this->roomRequest, $event, auth()->user());
        }
    }
}
