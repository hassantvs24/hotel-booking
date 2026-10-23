<?php

namespace App\Services\Notify;

use App\Models\Booking;
use App\Models\PayoutStatement;
use App\Models\Property;
use App\Models\RefundRequest;
use App\Models\ReviewSubmission;
use App\Models\RoomRequest;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * The bell lines a property's people get about reviews, refunds, payouts, offers that are about to run out and
 * guests arriving tomorrow. None of these may ever make the thing that caused them fail, so each one is
 * wrapped: a problem is written to the log and the real work carries on.
 */
class VendorAlerts
{
    public function review(ReviewSubmission $review): void
    {
        $this->safely(function () use ($review) {
            $review->loadMissing('booking.room.property', 'user');
            $property = $review->booking?->room?->property;

            if (!$property) {
                return;
            }

            $comment = trim((string) ($review->positive_comment ?: $review->negative_comment));
            $guest = $review->user?->name ?: 'A guest';
            $stars = number_format((float) $review->overall_rating, 1);

            $this->tell($this->watchers($property), 'review', "New review: {$stars}/10",
                $comment !== '' ? "{$guest}: " . Str::limit($comment, 90) : "{$guest} rated {$property->name}",
                ['property_id' => $property->id, 'review_submission_id' => $review->id, 'booking_number' => $review->booking?->booking_number], $property->name);
        });
    }

    /** The owner hears how a refund on one of their bookings went (the "asked for" line already exists) */
    public function refundChanged(RefundRequest $refund): void
    {
        $this->safely(function () use ($refund) {
            $line = match ($refund->status) {
                RefundRequest::STATUS_APPROVED  => ['Refund approved', 'approved'],
                RefundRequest::STATUS_REJECTED  => ['Refund declined', 'declined'],
                RefundRequest::STATUS_COMPLETED => ['Refund paid', 'paid back to the guest'],
                default                         => null,
            };

            if (!$line) {
                return;
            }

            $refund->loadMissing('booking.room.property', 'user');
            $booking = $refund->booking;
            $property = $booking?->room?->property;

            if (!$property) {
                return;
            }

            $amount = (float) ($refund->approved_amount ?? $refund->requested_amount);
            $money = $refund->status === RefundRequest::STATUS_REJECTED ? '' : ' of BDT ' . number_format($amount);

            $this->tell($this->team($property), 'refund', $line[0],
                "Booking #{$booking->booking_number}: the refund{$money} for " . ($refund->user?->name ?: 'the guest') . " was {$line[1]}.",
                ['property_id' => $property->id, 'refund_request_id' => $refund->id, 'booking_number' => $booking->booking_number]);
        });
    }

    public function payoutPaid(PayoutStatement $statement): void
    {
        $this->safely(function () use ($statement) {
            $vendor = User::find($statement->vendor_id);

            if (!$vendor) {
                return;
            }

            $method = $statement->method ? ' by ' . ucfirst($statement->method) : '';

            $this->tell(collect([$vendor]), 'payout', 'Payout sent',
                'BDT ' . number_format($statement->vendor_paisa / 100) . " was sent to you{$method}. Statement {$statement->number}.",
                ['payout_statement_id' => $statement->id]);
        });
    }

    /** An offer still waiting for the owner's answer runs out soon */
    public function bidExpiring(RoomRequest $bid): void
    {
        $this->safely(function () use ($bid) {
            $bid->loadMissing('room.property', 'user');
            $property = $bid->room?->property;

            if (!$property || !$bid->request_expiration_time) {
                return;
            }

            $minutes = max(1, (int) now()->diffInMinutes($bid->request_expiration_time, false));

            $this->tell($this->watchers($property), 'bid_expiring', 'An offer is about to run out',
                ($bid->user?->name ?: 'A guest') . "'s offer of BDT " . number_format((float) $bid->discount_price) . '/night on ' . ($bid->room->name ?? 'a room')
                    . " runs out in about {$minutes} minutes. Answer it before then.",
                ['property_id' => $property->id, 'room_request_id' => $bid->id], $property->name);
        });
    }

    /** Guests arriving tomorrow at one property: one line for the whole property, not one per guest */
    public function arrivingTomorrow(Property $property, Collection $bookings): void
    {
        $this->safely(function () use ($property, $bookings) {
            if ($bookings->isEmpty()) {
                return;
            }

            $names = $bookings->map(fn (Booking $b) => $b->main_guest_name ?: $b->user?->name ?: 'A guest')->unique()->values();
            $shown = $names->take(3)->implode(', ') . ($names->count() > 3 ? ' and ' . ($names->count() - 3) . ' more' : '');
            $n = $bookings->count();

            foreach ($this->team($property) as $user) {
                // the reminder runs once a day, but if it runs again nobody gets the same line twice
                $already = $user->notifications()->whereDate('created_at', today())
                    ->get(['data'])->contains(fn ($x) => ($x->data['type'] ?? null) === 'reminder' && (int) ($x->data['property_id'] ?? 0) === $property->id);

                if ($already) {
                    continue;
                }

                $this->tell(collect([$user]), 'reminder', $n === 1 ? '1 guest arrives tomorrow' : "{$n} guests arrive tomorrow",
                    "{$property->name}: {$shown}.", ['property_id' => $property->id, 'count' => $n]);
            }
        });
    }

    /** The platform admin: one line a day, for all hotels together (the hotels' own people get their own line) */
    public function arrivingTomorrowEverywhere(int $guests, int $hotels): void
    {
        $this->safely(function () use ($guests, $hotels) {
            if ($guests < 1) {
                return;
            }

            foreach (User::admins()->get() as $admin) {
                $already = $admin->notifications()->whereDate('created_at', today())
                    ->get(['data'])->contains(fn ($x) => ($x->data['type'] ?? null) === 'reminder' && !isset($x->data['property_id']));

                if (!$already) {
                    $this->tell(collect([$admin]), 'reminder', $guests === 1 ? '1 guest arrives tomorrow' : "{$guests} guests arrive tomorrow",
                        $hotels === 1 ? 'At 1 hotel.' : "Across {$hotels} hotels.", ['count' => $guests]);
                }
            }
        });
    }

    /** The owner and the staff who are switched on for this property, and the platform admins (who see every hotel) */
    public function watchers(Property $property): Collection
    {
        return $this->team($property)->merge(User::admins()->get())->unique('id')->values();
    }

    /** The owner and the staff who are switched on for this property */
    public function team(Property $property): Collection
    {
        $property->loadMissing('staffs');

        $ids = $property->staffs->filter(fn ($s) => (bool) $s->pivot->is_active)->pluck('id')->push($property->user_id)->unique();

        return User::whereIn('id', $ids)->get();
    }

    /** $where: the hotel's name, added to the title for the admin (who sees every hotel and needs to know which one) */
    private function tell(Collection $users, string $type, string $title, string $message, array $extra, ?string $where = null): void
    {
        $look = NotificationCategories::ALL[NotificationCategories::of($type)];

        foreach ($users as $user) {
            $line = $where && $user->is_admin ? "{$title} — {$where}" : $title;

            $user->notify(new AdminNotification($type, $line, $message, $look['icon'], $look['color'], $extra + ['always' => true]));
        }
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
