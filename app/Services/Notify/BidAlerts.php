<?php

namespace App\Services\Notify;

use App\Models\RoomRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use App\Notifications\BID\BidStatusNotification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Tells everyone concerned about what happened to an offer (a "bid"): the guest, the hotel's people and the platform
 * admin, each in the same words (see BidMessages), and never the person who did it themselves.
 *
 * It is only a bell. Every method here swallows its own errors: an offer, an answer, a payment or a refund is never
 * held up, undone or made to fail because a notification could not be made.
 */
class BidAlerts
{
    /** a guest sent an offer */
    public function submitted(RoomRequest $bid): void
    {
        $this->safely(fn () => $this->deliver($bid, 'submitted', $this->everyone($bid), null));
    }

    /** the hotel's side answered (countered, accepted or declined); $actor, if known, is not told of what they did themselves */
    public function answered(RoomRequest $bid, string $event, ?User $actor = null): void
    {
        $this->safely(fn () => $this->deliver($bid, $event, $this->everyone($bid), $actor));
    }

    /** the guest acted (withdrew an offer, accepted a counter): the hotel's people and the admin are told */
    public function guestActed(RoomRequest $bid, string $event, ?User $actor = null): void
    {
        $this->safely(fn () => $this->deliver($bid, $event, $this->everyone($bid, false), $actor));
    }

    /**
     * Offers that just ran out. $before: id => the status it had (Pending: the hotel never answered; Counter: the guest
     * never answered; Approved: the guest never paid).
     *
     * @param Collection<int,RoomRequest> $bids
     * @param array<int,string> $before
     */
    public function lapsed(Collection $bids, array $before): void
    {
        foreach ($bids as $bid) {
            $event = match ($before[$bid->id] ?? 'Pending') {
                'Counter'  => 'counter_lapsed',
                'Approved' => 'payment_lapsed',
                default    => 'expired',
            };

            $this->safely(fn () => $this->deliver($bid, $event, $this->everyone($bid), null));
        }
    }

    /** the guest has little time left to answer a counter or to pay: one reminder (the guest only) */
    public function guestReminder(RoomRequest $bid, string $event, int $minutes): bool
    {
        $sent = false;

        $this->safely(function () use ($bid, $event, $minutes, &$sent) {
            $bid->loadMissing('room.property', 'user');
            $guest = $bid->user;

            if (!$guest) {
                return;
            }

            // once per offer and moment, even if the job runs again
            $already = $guest->notifications()->where('created_at', '>=', now()->subDays(2))->get(['data'])
                ->contains(fn ($n) => (int) ($n->data['bid_id'] ?? 0) === (int) $bid->id && ($n->data['event'] ?? null) === $event);

            if ($already) {
                return;
            }

            $guest->notify(new BidStatusNotification($bid, $event, 'guest', ['minutes' => $minutes]));
            $sent = true;
        });

        return $sent;
    }

    /** @return array{guest:?User,team:Collection,admins:Collection} */
    private function everyone(RoomRequest $bid, bool $withGuest = true): array
    {
        $bid->loadMissing('room.property', 'user');
        $property = $bid->room?->property;

        return [
            'guest'  => $withGuest ? $bid->user : null,
            'team'   => $property ? app(VendorAlerts::class)->team($property) : collect(),
            'admins' => User::admins()->get(),
        ];
    }

    /** one line to each person, once each (a person who is the guest, the hotel and the admin at once gets the first) */
    private function deliver(RoomRequest $bid, string $event, array $who, ?User $actor): void
    {
        $told = collect($actor ? [$actor->id] : []);

        foreach ([['guest', collect([$who['guest']])->filter()], ['owner', $who['team']], ['admin', $who['admins']]] as [$audience, $users]) {
            foreach ($users as $user) {
                if ($told->contains($user->id)) {
                    continue;
                }

                $told->push($user->id);
                $this->safely(fn () => $this->tell($user, $bid, $event, $audience));
            }
        }
    }

    private function tell(User $user, RoomRequest $bid, string $event, string $audience): void
    {
        if ($audience !== 'admin') {
            $user->notify(new BidStatusNotification($bid, $event, $audience));

            return;
        }

        $m = BidMessages::for($bid, $event, 'admin');

        $user->notify(new AdminNotification(
            type:    $m['type'],
            title:   $m['title'],
            message: $m['body'],
            icon:    $m['icon'],
            color:   $m['color'],
            // each moment of each offer is its own line; a second counter (a new price) is a new moment
            extra:   ['room_request_id' => $bid->id . '_' . $event . '_' . ($bid->counter_price ?? ''), 'bid_id' => $bid->id, 'event' => $event, 'body' => $m['body']],
            channel: 'admin',
        ));
    }

    private function safely(callable $work): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
