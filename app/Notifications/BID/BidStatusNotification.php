<?php

namespace App\Notifications\BID;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class BidStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // $action: 'accepted' | 'countered' | 'declined' | 'counter_accepted' | 'expired'
    public function __construct(
        public $bid,
        public string $action
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    private function content(): array
    {
        $room = $this->bid->room->name ?? 'your room';

        return match ($this->action) {
            'accepted' => [
                'type'  => 'bid_accepted',
                'title' => 'Bid accepted!',
                'body'  => "Your offer for {$room} was accepted. Complete payment within 2 hours.",
                'url'   => '/request?tab=my-bids&bid=' . $this->bid->id,
            ],
            'countered' => [
                'type'  => 'bid_countered',
                'title' => 'Counter offer received',
                'body'  => "The owner countered your bid on {$room} — BDT " . number_format($this->bid->counter_price) . '/night.',
                'url'   => '/request?tab=my-bids&bid=' . $this->bid->id,
            ],
            'declined' => [
                'type'  => 'bid_declined',
                'title' => 'Bid declined',
                'body'  => "Your offer for {$room} was not accepted. " . (3 - $this->bid->bid_number) . " bid(s) remaining.",
                'url'   => '/request?tab=my-bids',
            ],
            'counter_accepted' => [
                'type'  => 'counter_accepted',
                'title' => 'Counter accepted',
                'body'  => "The guest accepted your counter offer on {$room}. Awaiting payment.",
                'url'   => '/request?tab=incoming',
            ],
            'expired' => [
                'type'  => 'bid_expired',
                'title' => 'Bid expired',
                'body'  => "Your offer for {$room} expired. You can submit a new bid.",
                'url'   => '/request?tab=my-bids',
            ],
            default => [
                'type'  => 'bid_update',
                'title' => 'Bid update',
                'body'  => 'Your bid status changed.',
                'url'   => '/request',
            ],
        };
    }

    public function toDatabase($notifiable): array
    {
        return array_merge($this->content(), [
            'bid_id'  => $this->bid->id,
            'room_id' => $this->bid->room_id,
        ]);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(array_merge(
            $this->content(),
            ['created_at' => now()->toISOString()]
        ));
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $c = $this->content();
        return (new WebPushMessage)
            ->title($c['title'])
            ->body($c['body'])
            ->icon('/icon-192.png')
            ->badge('/badge-72.png')
            ->data(['url' => $c['url']]);
    }
}
