<?php

namespace App\Services\Notify;

use App\Models\Booking;
use App\Models\BookingGroup;
use App\Models\User;
use App\Notifications\Admin\AdminNotification;
use Carbon\Carbon;
use Throwable;

/**
 * A booking that was never paid was cancelled by the clock and its room let go: the guest, the hotel's people and the
 * platform admin are told. Only a bell: it swallows its own errors and can never hold the clean-up up.
 */
class BookingAlerts
{
    public function released(Booking $booking, int $minutes): void
    {
        try {
            $booking->loadMissing('room.property', 'user');
            $property = $booking->room?->property;
            $hotel = $property?->name;
            $room = $booking->room?->name ?: 'the room';
            $dates = $this->dates($booking->checkin, $booking->checkout);
            $number = $booking->booking_number;

            // a cart of several rooms is one payment: its rooms are let go together, and each person hears of it once
            $ref = $booking->booking_group_id ? BookingGroup::where('id', $booking->booking_group_id)->value('group_ref') : null;
            $key = $ref ? ['group_ref' => $ref] : ['booking_number' => $number];

            $people = collect([['guest', collect([$booking->user])->filter()]])
                ->push(['owner', $property ? app(VendorAlerts::class)->team($property) : collect()])
                ->push(['admin', User::admins()->get()]);

            $told = collect();

            foreach ($people as [$audience, $users]) {
                foreach ($users as $user) {
                    if ($told->contains($user->id)) {
                        continue;
                    }

                    $told->push($user->id);

                    // the same booking (or cart) is told once, even if the clean-up looks at it again
                    $again = $user->notifications()->where('created_at', '>=', now()->subDays(2))->get(['data'])->contains(fn ($n) => ($n->data['type'] ?? null) === 'booking_released'
                        && (($ref && ($n->data['group_ref'] ?? null) === $ref) || ((string) ($n->data['booking_number'] ?? '') === (string) $number)));

                    if ($again) {
                        continue;
                    }

                    if ($audience === 'guest') {
                        $title = 'Booking cancelled: payment not received';
                        $body = "Booking #{$number} for {$room}" . ($hotel ? " at {$hotel}" : '') . ", {$dates} was cancelled because the payment did not arrive in time. The room was let go. You can book again.";
                        $url = '/profile/bookings';
                    } else {
                        $title = 'Unpaid booking released' . ($audience === 'admin' && $hotel ? " — {$hotel}" : '');
                        $body = "Booking #{$number} ({$room}, {$dates}" . ($booking->user?->name ? ", {$booking->user->name}" : '') . ") was cancelled: it was not paid within {$minutes} minutes, so the room is open again.";
                        $url = '/bookings';
                    }

                    $this->safely(fn () => $user->notify(new AdminNotification(
                        type: 'booking_released', title: $title, message: $body, icon: 'bx-time-five', color: 'amber',
                        extra: $key + ['body' => $body, 'url' => $url, 'booking_number' => $number, 'always' => true], channel: 'admin',
                    )));
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function dates($in, $out): string
    {
        try {
            $a = Carbon::parse($in);
            $b = Carbon::parse($out);

            return $a->isSameMonth($b) && $a->year === $b->year ? $a->format('j') . '–' . $b->format('j M') : $a->format('j M') . '–' . $b->format('j M');
        } catch (Throwable) {
            return 'your dates';
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
