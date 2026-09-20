<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $userId,
        public readonly array $messageIds,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.' . $this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'message_ids' => $this->messageIds,
            'read_at' => now()->toISOString(),
        ];
    }
}
