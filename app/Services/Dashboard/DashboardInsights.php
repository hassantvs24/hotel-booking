<?php

namespace App\Services\Dashboard;

use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyNameRequest;
use App\Models\PropertyReport;
use App\Models\PropertyRequest;
use App\Models\RefundRequest;
use App\Models\RoomRequest;
use App\Models\User;
use App\Services\Notify\NotificationPresenter;
use Illuminate\Database\Eloquent\Builder;

/**
 * What the dashboard shows beside the money: what is waiting for this person, today's check-ins, a few counts, and the
 * latest happenings. Everything is for ONE person: the platform admin sees every hotel, an owner only their own
 * (all of them, however many they have).
 */
class DashboardInsights
{
    /** The hotels this person looks after: null = all of them (the platform admin) */
    private function hotelIds(User $user): ?array
    {
        return $user->is_admin ? null : $user->properties()->pluck('id')->all();
    }

    private function bookings(?array $ids): Builder
    {
        return Booking::query()->when($ids !== null, fn ($q) => $q->whereHas('room', fn ($r) => $r->whereIn('property_id', $ids)));
    }

    public function build(User $user): array
    {
        $ids = $this->hotelIds($user);
        $tz = config('refund.timezone', 'Asia/Dhaka');
        $today = now($tz)->toDateString();
        $tomorrow = now($tz)->addDay()->toDateString();

        $stay = fn (string $date) => $this->bookings($ids)->where('status', 'approved')->whereDate('checkin', $date)->count();

        $offers = RoomRequest::query()
            ->when($ids !== null, fn ($q) => $q->whereIn('property_id', $ids))
            ->whereIn('status', RoomRequest::LIVE_STATUSES)
            ->where(fn ($q) => $q->whereNull('request_expiration_time')->orWhere('request_expiration_time', '>', now()));

        $offersWaiting = (clone $offers)->where('status', 'Pending')->count();

        $refundsAsked = RefundRequest::query()->where('status', RefundRequest::STATUS_REQUESTED)
            ->when($ids !== null, fn ($q) => $q->whereHas('booking.room', fn ($r) => $r->whereIn('property_id', $ids)))->count();

        // the same applications the "Property Request" page lists as pending: submitted ones, never half-filled drafts
        $applications = fn () => PropertyRequest::where('is_draft', false)->where('status', PropertyRequest::STATUS_PENDING)->count();

        // "Needs you": only what this person can act on, each with the page where they do it
        $needs = [
            ['key' => 'refunds', 'label' => 'Refund requests to answer', 'count' => $refundsAsked, 'to' => 'refunds', 'tone' => 'rose'],
            ['key' => 'offers', 'label' => 'Offers waiting for your answer', 'count' => $offersWaiting, 'to' => 'bids', 'tone' => 'amber'],
        ];

        if ($user->is_admin) {
            $needs[] = ['key' => 'properties', 'label' => 'Property applications to review', 'count' => $applications(), 'to' => 'propertyRequest', 'tone' => 'teal'];
            $needs[] = ['key' => 'names', 'label' => 'Name changes to decide', 'count' => PropertyNameRequest::where('status', 'pending')->count(), 'to' => 'property-name-requests', 'tone' => 'sky'];
            $needs[] = ['key' => 'reports', 'label' => "Guests' reports to look at", 'count' => PropertyReport::where('status', 'open')->count(), 'to' => 'property-reports', 'tone' => 'violet'];
        } else {
            $needs[] = ['key' => 'arrivals', 'label' => 'Guests arriving tomorrow', 'count' => $stay($tomorrow), 'to' => 'bookings', 'tone' => 'sky'];
        }

        return [
            'needs'    => $needs,
            'today'    => [
                'checkins'  => $stay($today),
                'checkouts' => $this->bookings($ids)->where('status', 'approved')->whereDate('checkout', $today)->count(),
                'tomorrow'  => $stay($tomorrow),
            ],
            'overview' => $user->is_admin
                ? ['properties' => Property::count(), 'offers' => (clone $offers)->count(), 'users' => User::count(), 'pending_properties' => $applications()]
                : ['properties' => count($ids), 'offers' => (clone $offers)->count()],
            'activity' => $user->notifications()->latest()->take(8)->get()->map(fn ($n) => NotificationPresenter::present($n))->values(),
        ];
    }

    /** The four newest bookings in plain words (no guest records, no ID numbers: only what the list shows) */
    public function recentBookings(User $user, int $limit = 4): array
    {
        return $this->bookings($this->hotelIds($user))
            ->with(['room:id,name,property_id', 'room.property:id,name', 'user:id,name'])
            ->latest('id')->take($limit)->get()
            ->map(fn (Booking $b) => [
                'id'             => $b->id,
                'booking_number' => $b->booking_number,
                'checkin'        => substr((string) $b->checkin, 0, 10),
                'checkout'       => substr((string) $b->checkout, 0, 10),
                'guests'         => (int) $b->adult + (int) $b->children,
                'room'           => $b->room?->name,
                'property'       => $b->room?->property?->name,
                'guest'          => $b->main_guest_name ?: $b->user?->name,
                'status'         => $b->status,
                'payment_status' => $b->payment_status,
                'amount'         => (float) $b->amount,
            ])->all();
    }
}
