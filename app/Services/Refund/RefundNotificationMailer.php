<?php

namespace App\Services\Refund;

use App\Mail\RefundCompletedMail;
use App\Models\RefundRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The "Your refund is complete" email.
 *
 * Sent once, when a refund reaches "completed" — straight away when the gateway answers at once, or
 * later when the status check finds SSLCommerz has finished it. Sending can never break a refund:
 * any failure is logged and swallowed.
 */
class RefundNotificationMailer
{
    /** Times in the email are shown in the guests' local time */
    private const TIMEZONE = 'Asia/Dhaka';

    public static function sendCompleted(RefundRequest $refund): void
    {
        self::send($refund, 'completed');
    }

    /** The gateway has accepted the refund but not paid it out yet (SSLCommerz can take days) */
    public static function sendProcessing(RefundRequest $refund): void
    {
        self::send($refund, 'processing');
    }

    private static function send(RefundRequest $refund, string $stage): void
    {
        try {
            $data = self::build($refund);

            if (!$data || !filled($data['email'])) {
                return;
            }

            $data['stage'] = $stage;

            // "on its way" has no completion time yet — the last step is when it was handed to the gateway
            if ($stage === 'processing') {
                $data['completed_at'] = Carbon::parse($refund->processed_at ?? now())->setTimezone(self::TIMEZONE)->format('d M Y, g:i A');
            }

            Mail::to($data['email'], $data['guest_name'])->send(new RefundCompletedMail($data));
        } catch (\Throwable $e) {
            Log::warning('Refund email was not sent: ' . $e->getMessage());
        }
    }

    /** Everything the email shows, as plain data (also used to preview the design) */
    public static function build(RefundRequest $refund): ?array
    {
        $refund->loadMissing(['booking.room.property.place.city', 'user', 'transaction']);

        $booking  = $refund->booking;
        $user     = $refund->user ?: $booking?->user;
        $property = $booking?->room?->property;

        if (!$booking) {
            return null;
        }

        $checkin  = Carbon::parse($booking->checkin);
        $checkout = Carbon::parse($booking->checkout);

        $paid   = (float) ($refund->original_amount ?? $booking->amount);
        $fee    = (float) ($refund->cancellation_fee ?? 0);
        $amount = (float) ($refund->approved_amount ?? 0);

        $address = $property?->address
            ?: trim(($property?->place?->full_address ?: '') ?: (string) $property?->place?->city?->name);

        $when = fn ($date) => $date
            ? Carbon::parse($date)->setTimezone(self::TIMEZONE)->format('d M Y, g:i A')
            : null;

        return [
            'email'          => $user?->email,
            'guest_name'     => $user?->name ?: 'Guest',
            'refund_number'  => $refund->refund_number,
            'booking_number' => $booking->booking_number,
            'hotel'          => $property?->name ?: 'Your hotel',
            'address'        => $address,
            'room'           => $booking->room?->name ?: 'Room',
            'checkin_date'   => $checkin->format('D, d M Y'),
            'checkout_date'  => $checkout->format('D, d M Y'),
            'nights'         => max(1, $checkin->diffInDays($checkout)),
            'paid'           => $paid,
            'fee'            => $fee,
            'fee_percent'    => $paid > 0 ? (int) round(($fee / $paid) * 100) : 0,
            'amount'         => $amount,
            'reason'         => self::reason($refund->reason_code),
            'refunded_to'    => self::paymentMethod($refund->transaction?->payment_method),
            'gateway_ref'    => $refund->gateway_refund_id,
            'stage'          => 'completed',
            'requested_at'   => $when($refund->requested_at ?? $refund->created_at),
            'approved_at'    => $when($refund->approved_at),
            'completed_at'   => $when($refund->completed_at ?? now()),
            'bookings_url'   => rtrim((string) config('app.frontend_url'), '/') . '/profile/bookings',
        ];
    }

    private static function reason(?string $code): ?string
    {
        return $code ? (config("refund.reasons.{$code}") ?: null) : null;
    }

    /** What the gateway calls itself is not what a guest recognises */
    private static function paymentMethod(?string $method): string
    {
        return match (true) {
            blank($method) => 'your original payment method',
            str_contains(strtolower($method), 'sslcomm') => 'your original payment method (via SSLCommerz)',
            default => $method,
        };
    }
}
