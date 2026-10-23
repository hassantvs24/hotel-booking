<?php

// Runs every 5 minutes via the scheduler (the arrival part only does anything once a day, at the hour set below).
//  - an offer still waiting for the owner, with about an hour left, gets one reminder
//  - at 18:00 each property hears about the guests who arrive tomorrow

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Property;
use App\Models\RoomRequest;
use App\Services\Notify\VendorAlerts;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class SendVendorReminders
{
    use Dispatchable, Queueable;

    public const ARRIVAL_HOUR = 18;

    public function handle(VendorAlerts $alerts): void
    {
        $this->expiringOffers($alerts);

        if (now()->hour >= self::ARRIVAL_HOUR) {
            $this->arrivals($alerts);
        }
    }

    /** Pending offers that run out within the next hour and were not reminded about yet */
    public function expiringOffers(VendorAlerts $alerts): int
    {
        $due = RoomRequest::where('status', 'Pending')
            ->whereNull('expiry_reminded_at')
            ->whereBetween('request_expiration_time', [now(), now()->addHour()])
            ->get();

        foreach ($due as $bid) {
            // marked first, so a failure in the bell can never turn into a reminder every five minutes
            RoomRequest::where('id', $bid->id)->whereNull('expiry_reminded_at')->update(['expiry_reminded_at' => now()]);
            $alerts->bidExpiring($bid);
        }

        return $due->count();
    }

    /** Confirmed bookings checking in tomorrow, grouped by property */
    public function arrivals(VendorAlerts $alerts): int
    {
        $bookings = Booking::where('status', 'approved')
            ->whereDate('checkin', today()->addDay())
            ->with('room', 'user')
            ->get();

        $groups = $bookings->groupBy(fn (Booking $b) => $b->room?->property_id)->forget('');

        foreach ($groups as $propertyId => $list) {
            if ($propertyId && ($property = Property::find($propertyId))) {
                $alerts->arrivingTomorrow($property, $list);
            }
        }

        $alerts->arrivingTomorrowEverywhere($bookings->count(), $groups->count());

        return $bookings->count();
    }
}
