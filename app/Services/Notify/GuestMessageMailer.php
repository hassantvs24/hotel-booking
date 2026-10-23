<?php

namespace App\Services\Notify;

use App\Mail\GuestMessageMail;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Tells a guest by email when the property (the owner, the staff or the Oystay team) writes to them in a booking
 * conversation: "You have a new message from <hotel>", the new lines in chat bubbles, and a button to the chat.
 *
 * - An email holds only lines that were not emailed before (and that the guest has not already read in the chat).
 * - At most one email per conversation every few minutes. The first line is emailed at once; lines written while
 *   the quiet time runs wait, and catchUp() (run every minute by the scheduler) sends them in ONE email as soon as
 *   the quiet time is over. So no line is ever left without a mail, and nobody is mailed line by line.
 * - Staff-only notes, system lines and the guest's own messages are never emailed.
 * - Sending can never break sending a message: a problem is only logged.
 */
class GuestMessageMailer
{
    /** Times in the email are shown in the guests' local time */
    private const TIMEZONE = 'Asia/Dhaka';

    /** At most one email per conversation in this many minutes */
    public const QUIET_MINUTES = 5;

    /** How many new lines one email shows (the rest are counted: "and 3 more in the chat") */
    private const SHOW = 5;

    /** Lines older than this that were somehow never emailed are left alone */
    private const LOOK_BACK_DAYS = 2;

    /** A line was just written: email it now if the conversation has been quiet, else it waits for catchUp() */
    public function notify(ChatMessage $message): void
    {
        try {
            if (!$this->worthAnEmail($message)) {
                return;
            }

            $conversationId = (int) $message->conversation_id;

            if (!Cache::add(self::key($conversationId), 1, now()->addMinutes(self::QUIET_MINUTES))) {
                return;
            }

            // after the answer has gone back to the person who typed, so sending the email never makes them wait
            app()->terminating(function () use ($conversationId) {
                $this->deliver($conversationId);
            });
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** The scheduler: send what has been waiting, for conversations whose quiet time is over. Returns how many emails went out. */
    public function catchUp(): int
    {
        try {
            $ids = DB::table('chat_messages as m')
                ->join('chat_conversations as c', 'c.id', '=', 'm.conversation_id')
                ->where('m.is_internal', false)->where('m.type', 'text')
                ->whereNull('m.deleted_at')->whereNull('c.deleted_at')
                ->whereColumn('m.sender_id', '!=', 'c.customer_id')
                ->whereRaw('m.id > coalesce(c.guest_emailed_until_id, 0)')
                ->where('m.created_at', '>=', now()->subDays(self::LOOK_BACK_DAYS))
                ->whereNotExists(fn ($q) => $q->from('chat_message_reads as r')->whereColumn('r.chat_message_id', 'm.id')->whereColumn('r.user_id', 'c.customer_id'))
                ->distinct()->limit(50)->pluck('m.conversation_id');
        } catch (Throwable $e) {
            report($e);

            return 0;
        }

        $sent = 0;

        foreach ($ids as $id) {
            if (Cache::add(self::key((int) $id), 1, now()->addMinutes(self::QUIET_MINUTES)) && $this->deliver((int) $id)) {
                $sent++;
            }
        }

        return $sent;
    }

    public static function key(int $conversationId): string
    {
        return "guest-chat-mail:{$conversationId}";
    }

    private function worthAnEmail(ChatMessage $message): bool
    {
        if ($message->is_internal || $message->type !== 'text') {
            return false;
        }

        $conversation = $message->conversation()->with('customer')->first();
        $guest = $conversation?->customer;

        // only the property writing to the guest; a guest with no email address (or who has left) is not written to
        return $guest && !$guest->trashed() && filled($guest->email)
            && (int) $message->sender_id !== (int) $conversation->customer_id;
    }

    /** Email the guest the new lines of one conversation. False when there was nothing to send (or it could not be). */
    private function deliver(int $conversationId): bool
    {
        try {
            $data = $this->build($conversationId);

            if (!$data) {
                return false;
            }

            Mail::to($data['email'], $data['guest_name'])->send(new GuestMessageMail($data));

            return true;
        } catch (Throwable $e) {
            Log::warning('Guest message email was not sent: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Everything the email shows, as plain data (also used to preview the design). It also records that these lines
     * are now taken care of, so they are never emailed again, even when this guest cannot be emailed at all.
     */
    public function build(int $conversationId): ?array
    {
        $conversation = ChatConversation::with(['customer', 'property', 'booking.room.property'])->find($conversationId);

        if (!$conversation) {
            return null;
        }

        // the property's lines since the last email, oldest first
        $fresh = $conversation->messages()
            ->where('is_internal', false)->where('type', 'text')
            ->where('sender_id', '!=', $conversation->customer_id)
            ->where('id', '>', (int) $conversation->guest_emailed_until_id)
            ->with(['attachments', 'sender'])
            ->orderBy('id')->limit(200)->get();

        if ($fresh->isEmpty()) {
            return null;
        }

        DB::table('chat_conversations')->where('id', $conversation->id)->update(['guest_emailed_until_id' => $fresh->max('id')]);

        // lines the guest has already read in the chat need no email
        $read = DB::table('chat_message_reads')->where('user_id', $conversation->customer_id)->whereIn('chat_message_id', $fresh->pluck('id'))->pluck('chat_message_id')->all();
        $new = $fresh->reject(fn (ChatMessage $m) => in_array($m->id, $read))->values();

        $guest = $conversation->customer;
        $booking = $conversation->booking;
        $property = $conversation->property ?: $booking?->room?->property;

        if ($new->isEmpty() || !$guest || $guest->trashed() || !filled($guest->email) || !$booking) {
            return null;
        }

        $shown = $new->take(-self::SHOW)->values();
        $senderIsTeam = (bool) $new->last()->sender?->is_admin;
        $checkin = Carbon::parse($booking->checkin);
        $checkout = Carbon::parse($booking->checkout);

        return [
            'email'          => $guest->email,
            'guest_name'     => $guest->name ?: 'Guest',
            'sender'         => $senderIsTeam ? 'Oystay support' : ($property?->name ?: 'your hotel'),
            'hotel'          => $property?->name ?: 'Your hotel',
            'messages'       => $shown->map(fn (ChatMessage $m) => [
                'text' => $this->clip($m),
                'time' => $m->created_at?->setTimezone(self::TIMEZONE)->format('d M, g:i A'),
            ])->all(),
            'more'           => max(0, $new->count() - $shown->count()),
            'booking_number' => $booking->booking_number,
            'checkin_date'   => $checkin->format('D d M Y'),
            'checkout_date'  => $checkout->format('D d M Y'),
            'messages_url'   => rtrim((string) config('app.frontend_url'), '/') . '/profile/messages?booking=' . $booking->booking_number,
        ];
    }

    /** One bubble's words: cut short when long, and "sent a file" for a message that is only an attachment */
    private function clip(ChatMessage $m): string
    {
        $text = trim((string) $m->message);
        $files = $m->attachments?->count() ?? 0;

        if ($text === '') {
            return $files > 1 ? "Sent you {$files} files. Open the chat to see them." : 'Sent you a file. Open the chat to see it.';
        }

        return Str::limit($text, 600) . ($files ? "\n(+ {$files} " . ($files > 1 ? 'files' : 'file') . ' in the chat)' : '');
    }
}
