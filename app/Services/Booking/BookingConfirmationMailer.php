<?php

namespace App\Services\Booking;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\BookingGroup;
use App\Models\RoomRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The "Your booking is confirmed" email.
 *
 * Called the moment a payment is confirmed (real gateway callback, the dev confirm-local button, and
 * for single bookings as well as multi-room groups). The callers already skip a booking that is
 * paid, so one payment means one email.
 *
 * Sending can never break a payment: any failure is logged and swallowed.
 */
class BookingConfirmationMailer
{
    /** Times in the email are shown in the guests' local time */
    private const TIMEZONE = 'Asia/Dhaka';

    public static function send(Booking|BookingGroup $subject): void
    {
        try {
            $data = self::build($subject);

            if (!$data || !filled($data['email'])) {
                return;
            }

            Mail::to($data['email'], $data['guest_name'])->send(new BookingConfirmedMail($data));
        } catch (\Throwable $e) {
            Log::warning('Booking confirmation email was not sent: ' . $e->getMessage());
        }
    }

    /** Everything the email shows, as plain data (also used to preview the design) */
    public static function build(Booking|BookingGroup $subject): ?array
    {
        $isGroup  = $subject instanceof BookingGroup;
        $bookings = $isGroup ? $subject->bookings : new EloquentCollection([$subject]);

        if ($bookings->isEmpty()) {
            return null;
        }

        $bookings->load(['room.property.place.city', 'user', 'transaction']);

        $first = $bookings->first();
        $user  = $first->user;

        $lines = $bookings->map(fn (Booking $b) => self::line($b))->values()->all();
        $total = $isGroup ? (float) $subject->total_amount : (float) $first->amount;

        // One payment covers the whole group, so one reference
        $transaction = $bookings->map->transaction->filter()->first();
        $paidAt      = $transaction?->created_at ?? now();

        return [
            'email'          => $user?->email,
            'guest_name'     => $user?->name ?: 'Guest',
            'reference'      => $isGroup ? $subject->group_ref : $first->booking_number,
            'is_group'       => $isGroup,
            'lines'          => $lines,
            'hotel_names'    => collect($lines)->pluck('hotel')->unique()->values()->all(),
            'total'          => $total,
            'payment_method' => self::paymentMethod($transaction?->payment_method),
            'payment_ref'    => $transaction?->transaction_reference,
            'paid_at'        => Carbon::parse($paidAt)->setTimezone(self::TIMEZONE)->format('d M Y, g:i A'),
            'policy'         => self::refundPolicy(),
            'bookings_url'   => rtrim((string) config('app.frontend_url'), '/') . '/profile/bookings',
            'app_name'       => 'Oystay',
        ];
    }

    private static function line(Booking $b): array
    {
        $room     = $b->room;
        $property = $room?->property;
        $checkin  = Carbon::parse($b->checkin);
        $checkout = Carbon::parse($b->checkout);
        $nights   = max(1, $checkin->diffInDays($checkout));

        // Address as the property gives it; fall back to where the place says it is
        $address = $property?->address
            ?: trim(($property?->place?->full_address ?: '') ?: (string) $property?->place?->city?->name);

        $line = [
            'booking_number' => $b->booking_number,
            'hotel'          => $property?->name ?: 'Your hotel',
            'address'        => $address,
            'hotel_phone'    => $property?->phone_number,
            'room'           => $room?->name ?: 'Room',
            'rooms'          => max(1, (int) ($b->rooms ?? 1)),
            'checkin_date'   => $checkin->format('D, d M Y'),
            'checkin_time'   => self::time($property?->check_in_time, '2:00 PM'),
            'checkout_date'  => $checkout->format('D, d M Y'),
            'checkout_time'  => self::time($property?->check_out_time, '12:00 PM'),
            'nights'         => $nights,
            'adults'         => (int) $b->adult,
            'children'       => (int) $b->children,
            'amount'         => (float) $b->amount,
            'per_night'      => (float) $b->amount / $nights,
            'bid'            => null,
        ];

        // A booking that came from an accepted offer: show what was agreed against the listed price
        if ($b->room_request_id && ($bid = RoomRequest::find($b->room_request_id))) {
            $listed = (float) ($room?->base_price ?? 0);
            $agreed = (float) $bid->agreed_price;

            $line['bid'] = [
                'agreed_per_night' => $agreed,
                'listed_per_night' => $listed,
                'saved_total'      => max(0, ($listed - $agreed) * $nights),
                'saved_percent'    => $listed > 0 ? (int) round((($listed - $agreed) / $listed) * 100) : 0,
            ];
        }

        return $line;
    }

    /** What the gateway calls itself is not what a guest recognises */
    private static function paymentMethod(?string $method): string
    {
        return match (true) {
            blank($method) => 'Online payment',
            str_contains(strtolower($method), 'sslcomm') => 'Online payment (SSLCommerz)',
            default => $method,
        };
    }

    /** "14:00:00" → "2:00 PM" */
    private static function time(?string $value, string $fallback): string
    {
        try {
            return $value ? Carbon::parse($value)->format('g:i A') : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /** The refund tiers from config/refund.php, worded for a guest, most generous first */
    private static function refundPolicy(): array
    {
        $tiers = collect(config('refund.tiers', []))->sortByDesc('minimum_hours_before_checkin')->values();
        $rows  = [];
        $upper = null;   // hours — the tier above covers everything beyond this

        foreach ($tiers as $tier) {
            $hours   = (int) $tier['minimum_hours_before_checkin'];
            $percent = (int) $tier['refund_percentage'];

            $when = $upper === null
                ? 'More than ' . self::duration($hours) . ' before check-in'
                : 'Between ' . self::duration($hours) . ' and ' . self::duration($upper) . ' before check-in';

            $rows[] = [
                'when'    => $when,
                'outcome' => $percent >= 100 ? 'Full refund' : $percent . '% refunded',
                'percent' => $percent,
            ];
            $upper = $hours;
        }

        $rows[] = ['when' => 'Once check-in day has started', 'outcome' => 'No refund', 'percent' => 0];

        return $rows;
    }

    private static function duration(int $hours): string
    {
        if ($hours >= 24 && $hours % 24 === 0) {
            $days = intdiv($hours, 24);

            return $days . ' day' . ($days > 1 ? 's' : '');
        }

        return $hours . ' hour' . ($hours > 1 ? 's' : '');
    }
}
