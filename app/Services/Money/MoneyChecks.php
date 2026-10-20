<?php

namespace App\Services\Money;

use App\Models\Booking;
use App\Models\LedgerEntry;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

/**
 * Finds bookings whose real record no longer agrees with the money that was booked for them.
 *
 * The money ledger never changes, but the booking it came from can: an admin can delete a paid booking, set it to
 * "cancelled" by hand without any refund, move it to another room or other dates. Until a person has looked, the
 * vendor's money for such a booking is held back (it will not show as "ready to pay").
 *
 * A booking that was refunded through the real refund flow is fine: it has refund lines in the ledger.
 */
class MoneyChecks
{
    public const TEXT = [
        'booking_deleted'   => 'The booking was deleted, but the guest was never refunded.',
        'status_changed'    => 'The booking was set to cancelled or refunded by hand, but no refund was made.',
        'not_paid'          => 'A payment was recorded, but the booking does not say it is paid.',
        'dates_changed'     => 'The booking dates were changed after the guest paid.',
        'room_moved'        => 'The booking was moved to a room of another property after the guest paid.',
        'duplicate_payment' => 'The guest was charged more than once for this booking.',
        'manual_hold'       => 'The money for this booking is on hold (set by the admin).',
    ];

    /** @var array<int,string>|null booking number => reason code */
    private ?array $cache = null;

    public function forget(): void
    {
        $this->cache = null;
    }

    /** @return array<int,string> booking number => reason code, for bookings that still hold money */
    public function unsafe(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        // One query over the ledger, joined to the bookings (deleted ones included) and their rooms. Only bookings that
        // still hold money are looked at, and only the ones that no longer agree with their money come back.
        // (A booking that is gone altogether has no row to compare: its ledger line says so itself and is already held.)
        $rows = DB::select("
            select booking_number, reason from (
                select n.booking_number,
                       case
                           when n.payments > 1 then 'duplicate_payment'
                           when b.id is null then null
                           when b.deleted_at is not null and n.refunds = 0 then 'booking_deleted'
                           when b.status in ('cancelled', 'refunded') and n.refunds = 0 then 'status_changed'
                           when b.deleted_at is null and b.payment_status not in ('paid', 'refunded') and n.refunds = 0 then 'not_paid'
                           when n.checkout is not null and substr(b.checkout, 1, 10) <> substr(n.checkout, 1, 10) then 'dates_changed'
                           when n.property_id is not null and r.property_id is not null and r.property_id <> n.property_id then 'room_moved'
                           else null
                       end as reason
                from (
                    select booking_number, sum(gross_paisa) as net, max(checkout_date) as checkout, max(property_id) as property_id,
                           sum(type = 'refund') as refunds, sum(type = 'payment') as payments
                    from ledger_entries
                    where booking_number is not null
                    group by booking_number
                    having sum(gross_paisa) > 0
                ) n
                left join bookings b on b.booking_number = n.booking_number
                left join rooms r on r.id = b.room_id
            ) found
            where reason is not null
        ");

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->booking_number] = $row->reason;
        }

        // What a person has already looked at and let go stays released, for that reason only
        if ($out) {
            $released = DB::table('money_reviews')->whereIn('booking_number', array_keys($out))->get(['booking_number', 'reason'])
                ->mapWithKeys(fn ($r) => [$r->booking_number . '|' . $r->reason => true]);

            $out = array_filter($out, fn ($reason, $number) => !isset($released[$number . '|' . $reason]), ARRAY_FILTER_USE_BOTH);
        }

        // A hold the admin put on a booking always counts, and a review cannot lift it (only lifting the hold does)
        foreach (DB::table('money_holds')->pluck('booking_number') as $number) {
            $out[(int) $number] = 'manual_hold';
        }

        return $this->cache = $out;
    }

    public function reasonFor(?int $bookingNumber): ?string
    {
        return $bookingNumber ? ($this->unsafe()[$bookingNumber] ?? null) : null;
    }

    public static function text(string $code): string
    {
        return self::TEXT[$code] ?? $code;
    }
}
