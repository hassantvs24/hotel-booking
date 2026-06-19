<?php

namespace App\Notifications\Booking;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // $role: 'guest' | 'owner'
    public function __construct(
        public $booking,
        public string $role = 'guest'
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    private function content(): array
    {
        $room     = $this->booking->room->name ?? 'a room';
        $hotel    = $this->booking->room->property->name ?? 'the hotel';
        $checkin  = Carbon::parse($this->booking->checkin)->format('d M Y');
        $checkout = Carbon::parse($this->booking->checkout)->format('d M Y');

        if ($this->role === 'owner') {
            return [
                'type'  => 'booking_confirmed_owner',
                'title' => 'New booking confirmed',
                'body'  => "{$room} booked · {$checkin} → {$checkout}",
                'url'   => '/profile/bookings',
            ];
        }

        return [
            'type'  => 'booking_confirmed_guest',
            'title' => 'Booking confirmed!',
            'body'  => "{$room} at {$hotel} · {$checkin} → {$checkout}",
            'url'   => '/profile/bookings',
        ];
    }

    public function toDatabase($notifiable): array
    {
        return array_merge($this->content(), [
            'booking_number' => $this->booking->booking_number,
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
