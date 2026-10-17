<?php

namespace App\Notifications\Property;

use App\Models\Property;
use App\Models\PropertyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * The bell message an owner gets when their property is approved, and again when it goes live.
 * "approved" = the application was accepted but the property is not public yet; "published" = guests can find it now.
 */
class PropertyStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // $kind: 'approved' | 'published'
    public function __construct(
        public string $kind,
        public string $propertyName,
        public ?int $propertyId = null,
        public ?int $requestId = null,
        public int $roomCount = 0,
    ) {}

    public static function approved(PropertyRequest $request): self
    {
        return new self('approved', (string) ($request->property_title ?: 'Your property'), $request->property_id, $request->id);
    }

    public static function published(Property $property): self
    {
        return new self('published', (string) $property->name, $property->id, $property->request?->id, $property->rooms()->count());
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    private function content(): array
    {
        if ($this->kind === 'published') {
            return [
                'type'  => 'property_published',
                'title' => 'Your property is live!',
                // guests only see a property in search once it has a room they could book
                'body'  => $this->roomCount > 0
                    ? "{$this->propertyName} is now live on Oystay, guests can find and book it."
                    : "{$this->propertyName} is published. Guests will see it in search as soon as you add your first room, so add a room to start getting bookings.",
                'url'   => $this->propertyId ? '/properties/' . $this->propertyId . '/details/' . Str::slug($this->propertyName) : '/profile/request-property',
            ];
        }

        return [
            'type'  => 'property_approved',
            'title' => 'Your property was approved',
            'body'  => "{$this->propertyName} was approved. We are getting it ready to go live, it will not show in search until then. We will tell you the moment it is published.",
            'url'   => '/profile/request-property',
        ];
    }

    public function toDatabase($notifiable): array
    {
        return array_merge($this->content(), ['property_id' => $this->propertyId, 'property_request_id' => $this->requestId]);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(array_merge($this->content(), ['created_at' => now()->toISOString()]));
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
