<?php

namespace App\Notifications\BID;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class BidReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $bid,
        public $room,
        public $guest
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'       => 'bid_received',
            'title'      => 'New bid on ' . $this->room->name,
            'body'       => ($this->guest->name ?? 'A guest') . ' offered BDT ' . number_format($this->bid->discount_price) . '/night',
            'action_url' => '/request?tab=incoming&bid=' . $this->bid->id,
            'bid_id'     => $this->bid->id,
            'room_id'    => $this->room->id,
            'bid_number' => $this->bid->bid_number,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'       => 'bid_received',
            'title'      => 'New bid on ' . $this->room->name,
            'body'       => ($this->guest->name ?? 'A guest') . ' offered BDT ' . number_format($this->bid->discount_price) . '/night',
            'bid_id'     => $this->bid->id,
            'created_at' => now()->toISOString(),
        ]);
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('New bid — ' . $this->room->name)
            ->body(($this->guest->name ?? 'A guest') . ' offered BDT ' . number_format($this->bid->discount_price) . '/night')
            ->icon('/icon-192.png')
            ->badge('/badge-72.png')
            ->data(['url' => '/request?tab=incoming&bid=' . $this->bid->id]);
    }
}
