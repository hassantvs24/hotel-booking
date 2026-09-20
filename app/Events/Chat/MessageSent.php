<?php

namespace App\Events\Chat;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ChatMessage $chatMessage) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.' . $this->chatMessage->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->chatMessage->id,
                'conversation_id' => $this->chatMessage->conversation_id,
                'sender_id' => $this->chatMessage->sender_id,
                'sender' => [
                    'id' => $this->chatMessage->sender?->id,
                    'name' => $this->chatMessage->sender?->name,
                ],
                'message' => $this->chatMessage->message,
                'type' => $this->chatMessage->type,
                'is_internal' => false,
                'attachments' => $this->chatMessage->attachments->map(fn ($file) => [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'download_url' => url("/api/chat/attachments/{$file->id}"),
                ])->values(),
                'read_by' => [],
                'created_at' => $this->chatMessage->created_at?->toISOString(),
            ],
        ];
    }
}

