<?php

namespace App\Services\Notify;

use App\Models\ChatMessage;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Tells a property's owner and active staff when a guest writes to them.
 * Messages that arrive one after another are kept as ONE bell line ("3 new messages from Rahim"), so a chatty guest
 * does not bury the rest of the bell; opening the conversation clears it.
 */
class MessageNotifier
{
    public function notify(ChatMessage $message): void
    {
        try {
            $this->send($message);
        } catch (Throwable $e) {
            // a bell that fails must never stop a message from being sent
            report($e);
        }
    }

    private function send(ChatMessage $message): void
    {
        if ($message->is_internal || $message->type !== 'text') {
            return;
        }

        $conversation = $message->conversation()->with('property.staffs')->first();
        $property = $conversation?->property;

        // only a guest writing to the property is news for the property's people
        if (!$property || (int) $message->sender_id !== (int) $conversation->customer_id) {
            return;
        }

        $guest = $message->sender?->name ?: 'A guest';
        $text = trim((string) $message->message);
        $snippet = $text !== '' ? Str::limit($text, 90) : 'Sent you a file';

        $recipientIds = $property->staffs->filter(fn ($s) => (bool) $s->pivot->is_active)->pluck('id')
            ->push($property->user_id)->unique()->reject(fn ($id) => (int) $id === (int) $message->sender_id);

        foreach (User::whereIn('id', $recipientIds)->get() as $user) {
            $earlier = $this->unreadFor($user, $conversation->id);
            $count = $earlier->sum(fn ($n) => max(1, (int) ($n->data['count'] ?? 1))) + 1;
            $earlier->each->delete();

            $user->notify(new AdminNotification(
                'message',
                $count > 1 ? "{$count} new messages from {$guest}" : "New message from {$guest}",
                $snippet,
                'bx-message-dots',
                'teal',
                ['conversation_id' => $conversation->id, 'property_id' => $property->id, 'count' => $count, 'always' => true],
            ));
        }
    }

    /** The unread message notifications this person has for one conversation */
    private function unreadFor(User $user, int $conversationId)
    {
        return $user->unreadNotifications()->get()->filter(
            fn ($n) => ($n->data['type'] ?? null) === 'message' && (int) ($n->data['conversation_id'] ?? 0) === $conversationId
        );
    }

    /** The person opened the conversation: its bell line is done */
    public function clear(User $user, int $conversationId): void
    {
        $this->unreadFor($user, $conversationId)->each->markAsRead();
    }
}
