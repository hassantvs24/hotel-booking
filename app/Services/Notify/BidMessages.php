<?php

namespace App\Services\Notify;

use App\Models\RoomRequest;
use Carbon\Carbon;
use Throwable;

/**
 * Every line anyone reads about an offer (a "bid"), in one place: the guest, the hotel's people and the platform admin
 * all get the same wording for the same moment, always saying which of the (at most 3) offers it is, the hotel, the room
 * and the dates. It only builds words, and works with whatever it is given: a missing room or guest gives a plainer line,
 * never an error.
 *
 * events: submitted, countered, accepted, declined, withdrawn, counter_accepted,
 *         expired (the hotel did not answer), counter_lapsed (the guest did not answer a counter),
 *         payment_lapsed (accepted, but not paid in time), counter_expiring, pay_expiring
 * audiences: guest, owner (the hotel's people), admin
 */
final class BidMessages
{
    public const EVENTS = ['submitted', 'countered', 'accepted', 'declined', 'withdrawn', 'counter_accepted', 'expired', 'counter_lapsed', 'payment_lapsed', 'counter_expiring', 'pay_expiring'];

    /** @return array{type:string,event:string,title:string,body:string,url:string,icon:string,color:string} */
    public static function for(object $bid, string $event, string $audience = 'guest', array $more = []): array
    {
        $event = $event === 'expired_unanswered' ? 'expired' : $event;
        $c = self::context($bid);
        $tag = "Bid {$c['n']} of {$c['max']}";
        $you = $audience === 'guest';
        $who = $c['guest'];

        $line = match ($event) {
            'submitted' => $you
                ? ["{$tag} sent", "You offered {$c['offer']}/night for {$c['room']}{$c['at']}, {$c['dates']}. The hotel has {$c['hours']} hours to answer."]
                : ["New bid · {$tag}", "{$who} offered {$c['offer']}/night for {$c['room']}, {$c['dates']} ({$c['nights']}). Answer within {$c['hours']} hours."],
            'countered' => $you
                ? ["Counter offer · {$tag}", "{$c['hotel']} countered your {$c['offer']} offer with {$c['counter']}/night for {$c['room']}, {$c['dates']}. Answer within {$c['hours']} hours. {$c['left_line']}"]
                : ["Counter sent · {$tag}", "A counter of {$c['counter']}/night (offer {$c['offer']}) was sent to {$who} for {$c['room']}, {$c['dates']}."],
            'accepted' => $you
                ? ["{$tag} accepted!", "{$c['hotel']} accepted {$c['agreed']}/night for {$c['room']}, {$c['dates']}. Pay within {$c['pay_hours']} hours to confirm your booking."]
                : ["Offer accepted · {$tag}", "{$who}'s offer of {$c['agreed']}/night for {$c['room']}, {$c['dates']} was accepted. Waiting for payment ({$c['pay_hours']} hours)."],
            'declined' => $you
                ? ["{$tag} declined", "{$c['hotel']} did not accept {$c['offer']}/night for {$c['room']}, {$c['dates']}. {$c['left_line']}"]
                : ["Offer declined · {$tag}", "{$who}'s offer of {$c['offer']}/night for {$c['room']}, {$c['dates']} was declined."],
            'withdrawn' => ["Offer withdrawn · {$tag}", "{$who} withdrew their offer of {$c['offer']}/night for {$c['room']}, {$c['dates']}."],
            'counter_accepted' => ["Counter accepted · {$tag}", "{$who} accepted your counter of {$c['counter']}/night for {$c['room']}, {$c['dates']}. Waiting for payment ({$c['pay_hours']} hours)."],
            'expired' => $you
                ? ["{$tag} expired", "{$c['hotel']} did not answer your {$c['offer']} offer for {$c['room']}, {$c['dates']} in time. This did not use up a bid, so you can offer again."]
                : ["Offer expired · {$tag}", "{$who}'s offer of {$c['offer']}/night for {$c['room']}, {$c['dates']} ran out before it was answered."],
            'counter_lapsed' => $you
                ? ["Counter offer expired · {$tag}", "You did not answer {$c['hotel']}'s counter of {$c['counter']}/night for {$c['room']}, {$c['dates']} in time. You can send a new offer."]
                : ["Counter expired · {$tag}", "{$who} did not answer your counter of {$c['counter']}/night for {$c['room']}, {$c['dates']} in time."],
            'payment_lapsed' => $you
                ? ["Time to pay ran out · {$tag}", "The {$c['pay_hours']} hours to pay for {$c['room']}{$c['at']}, {$c['dates']} are over, so the room was released. You can send a new offer."]
                : ["Payment missed · {$tag}", "{$who} did not pay in time for {$c['room']}, {$c['dates']}, so the room is open again."],
            'counter_expiring' => ["Counter offer ends soon · {$tag}", "{$c['hotel']}'s counter of {$c['counter']}/night for {$c['room']}, {$c['dates']} ends in about " . ($more['minutes'] ?? 60) . ' minutes. Answer it now.'],
            'pay_expiring' => ["Pay soon · {$tag}", "Your accepted offer{$c['at']} for {$c['room']}, {$c['dates']} is released in about " . ($more['minutes'] ?? 30) . ' minutes if you do not pay.'],
            default => ["Bid update · {$tag}", 'Your bid changed.'],
        };

        // the platform admin sees every hotel, so the title says which one
        $title = $audience === 'admin' && $c['hotel_real'] ? "{$line[0]} — {$c['hotel_real']}" : $line[0];

        [$icon, $color] = match ($event) {
            'submitted' => ['bx-tag', 'blue'],
            'countered' => ['bx-transfer', 'blue'],
            'accepted', 'counter_accepted' => ['bx-check-circle', 'green'],
            'declined' => ['bx-x-circle', 'red'],
            default => ['bx-time-five', 'amber'],
        };

        $id = data_get($bid, 'id');

        return [
            'type'  => self::type($event, $audience),
            'event' => $event,
            'title' => $title,
            'body'  => $line[1],
            'url'   => $you ? ($event === 'submitted' ? '/profile/my-bids' : '/profile/my-bids?bid=' . $id) : '/request?tab=incoming&bid=' . $id,
            'icon'  => $icon,
            'color' => $color,
        ];
    }

    private static function type(string $event, string $audience): string
    {
        return match ($event) {
            'submitted' => $audience === 'guest' ? 'bid_submitted' : 'bid_received',
            'countered' => 'bid_countered',
            'accepted' => 'bid_accepted',
            'declined' => 'bid_declined',
            'withdrawn' => 'bid_withdrawn',
            'counter_accepted' => 'counter_accepted',
            'counter_expiring', 'pay_expiring' => 'bid_expiring',
            default => 'bid_expired',
        };
    }

    /** the facts a line is made of, each already in words */
    private static function context(object $bid): array
    {
        $max = RoomRequest::MAX_BIDS;
        $n = (int) data_get($bid, 'bid_number');
        $n = $n >= 1 ? min($n, $max) : 1;   // an old row without a number is "1"

        $hotel = data_get($bid, 'room.property.name');
        $offer = (float) data_get($bid, 'discount_price', 0);
        $counter = data_get($bid, 'counter_price');
        $left = self::left($bid);

        return [
            'n'          => $n,
            'max'        => $max,
            'hotel'      => $hotel ?: 'The hotel',
            'hotel_real' => $hotel ?: null,
            'at'         => $hotel ? " at {$hotel}" : '',
            'room'       => data_get($bid, 'room.name') ?: 'the room',
            'guest'      => data_get($bid, 'user.name') ?: 'A guest',
            'dates'      => self::dates(data_get($bid, 'check_in'), data_get($bid, 'check_out')),
            'nights'     => self::nights(data_get($bid, 'check_in'), data_get($bid, 'check_out')),
            'offer'      => self::money($offer),
            'counter'    => self::money($counter !== null ? (float) $counter : $offer),
            'agreed'     => self::money($counter !== null ? (float) $counter : $offer),
            'hours'      => (int) config('bidding.pending_hours', 8),
            'pay_hours'  => (int) config('bidding.payment_hours', 2),
            'left_line'  => $left === null ? '' : ($left > 0 ? "You have {$left} more " . ($left === 1 ? 'bid' : 'bids') . ' for this stay.' : "You have used all {$max} bids for this stay."),
        ];
    }

    /** offers the guest still has for this room and these dates (null when it cannot be told) */
    private static function left(object $bid): ?int
    {
        try {
            $user = data_get($bid, 'user_id');
            $room = data_get($bid, 'room_id');
            $in = data_get($bid, 'check_in');
            $out = data_get($bid, 'check_out');

            if (!$user || !$room || !$in || !$out) {
                return null;
            }

            return max(0, RoomRequest::MAX_BIDS - RoomRequest::offersUsed((int) $user, (int) $room, $in, $out));
        } catch (Throwable) {
            return null;
        }
    }

    public static function money(float $amount): string
    {
        return 'BDT ' . number_format($amount);
    }

    private static function dates($in, $out): string
    {
        try {
            if (!$in || !$out) {
                return 'your dates';
            }

            $a = Carbon::parse($in);
            $b = Carbon::parse($out);

            if ($a->isSameMonth($b) && $a->year === $b->year) {
                return $a->format('j') . '–' . $b->format('j M');
            }

            return $a->format('j M') . '–' . $b->format('j M');
        } catch (Throwable) {
            return 'your dates';
        }
    }

    private static function nights($in, $out): string
    {
        try {
            $n = max(1, (int) round(Carbon::parse($in)->startOfDay()->diffInDays(Carbon::parse($out)->startOfDay())));

            return $n . ($n === 1 ? ' night' : ' nights');
        } catch (Throwable) {
            return '1 night';
        }
    }
}
