<?php

namespace App\Notifications\BID;

use App\Services\Notify\BellFields;
use App\Services\Notify\BidMessages;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class BidStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // $action: submitted | countered | accepted | declined | withdrawn | counter_accepted | expired | counter_lapsed |
    //          payment_lapsed | counter_expiring | pay_expiring   (see BidMessages for what each says)
    // $audience: guest | owner (the hotel's people)
    public function __construct(
        public $bid,
        public string $action,
        public string $audience = 'guest',
        public array $more = []
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    private function content(): array
    {
        return BidMessages::for($this->bid, $this->action, $this->audience, $this->more);
    }

    public function toDatabase($notifiable): array
    {
        return BellFields::add(array_merge($this->content(), [
            'bid_id'     => $this->bid->id,
            'room_id'    => $this->bid->room_id,
            'bid_number' => data_get($this->bid, 'bid_number'),
        ]));
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(BellFields::add(array_merge(
            $this->content(),
            ['bid_id' => $this->bid->id, 'created_at' => now()->toISOString()]
        )));
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
