<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;

class AdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $type,
        private string $title,
        private string $message,
        private string $icon    = 'bx-bell',
        private string $color   = 'teal',
        private array  $extra   = [],
        private string $channel = 'admin', // 'admin' | 'property:{id}' | 'user'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Override the broadcast channel so notifications
     * go to the correct channel per audience:
     *
     *  admin       → private-admin. Notifications
     *  property:X  → private-property.X.notifications
     *  user        → private-App.Models.User.{id}  (default Laravel behaviour)
     */
    public function broadcastOn(): array
    {
        if ($this->channel === 'admin') {
            return [new PrivateChannel('admin.notifications')];
        }

        if (str_starts_with($this->channel, 'property:')) {
            $propertyId = str_replace('property:', '', $this->channel);
            return [new PrivateChannel("property.{$propertyId}.notifications")];
        }

        // 'user' — falls back to App.Models.User.{id} via receivesBroadcastNotificationsOn
        return [new PrivateChannel('App.Models.User.' . ($this->extra['user_id'] ?? 0))];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    private function payload(): array
    {
        return array_merge([
            'type'       => $this->type,
            'title'      => $this->title,
            'message'    => $this->message,
            'icon'       => $this->icon,
            'color'      => $this->color,
            'created_at' => now()->toISOString(),
        ], $this->extra);
    }
}
