<?php

namespace App\Notifications\Property;

use App\Models\PropertyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * The bell message an owner gets when their application is sent back: a document was refused, or the whole
 * application was rejected. The reason is in the message and the link opens the wizard on their application.
 */
class PropertyRequestReturnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // $kind: 'documents' (an NID / licence was refused) | 'rejected' (the team rejected the application)
    public function __construct(
        public PropertyRequest $propertyRequest,
        public string $kind = 'documents'
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    private function content(): array
    {
        $name   = $this->propertyRequest->property_title ?: 'your property';
        $reason = trim((string) $this->propertyRequest->admin_message);

        return [
            'type'  => $this->kind === 'documents' ? 'property_documents' : 'property_rejected',
            'title' => $this->kind === 'documents' ? 'Your documents need fixing' : 'Your property application was sent back',
            'body'  => $name . ($reason !== '' ? ': ' . $reason : ' needs a few changes.') . ' Tap to fix it and submit again.',
            'url'   => '/add-properties?ref=' . $this->propertyRequest->unique_request_number,
        ];
    }

    public function toDatabase($notifiable): array
    {
        return array_merge($this->content(), ['property_request_id' => $this->propertyRequest->id]);
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
