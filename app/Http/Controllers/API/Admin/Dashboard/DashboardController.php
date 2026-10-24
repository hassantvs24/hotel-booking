<?php

namespace App\Http\Controllers\API\Admin\Dashboard;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Room;
use App\Services\Dashboard\DashboardInsights;
use App\Services\Money\LedgerSync;
use App\Services\Money\VendorMoney;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Property scope — admin sees the whole platform, a merchant sees
        // only rooms/bookings/reviews for properties they own (all of
        // them, not just the first — a merchant can own several).
        $propertyIds = $user->is_admin ? null : $user->properties()->pluck('id');
        $scoped = fn($query) => $propertyIds === null
            ? $query
            : $query->whereHas('room', fn($q) => $q->whereIn('property_id', $propertyIds));

        $insights = app(DashboardInsights::class);
        $bookingCount = $scoped(Booking::query())->count();
        $roomCount = $propertyIds === null
            ? Room::count()
            : Room::whereIn('property_id', $propertyIds)->count();
        $reviewCount = $propertyIds === null
            ? Review::count()
            : Review::whereIn('property_id', $propertyIds)->count();
        $bookingCountsByStatus = $scoped(Booking::query())
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Earnings, in the words of whoever is looking (from the money ledger; see VendorMoney):
        //  - the platform admin earns the COMMISSION only (the VAT and the service charge are the vendors')
        //  - a vendor earns their SHARE: everything the guests paid for their properties, less the commission
        app(LedgerSync::class)->run(2.0);
        $money = app(VendorMoney::class);
        if ($user->is_admin) {
            $earnings = ['kind' => 'platform'] + $money->platform();
            $totalEarnings = $earnings['commission_paisa'] / 100;
            $monthly = $earnings['month']['commission_paisa'] / 100;
        } else {
            $earnings = ['kind' => 'vendor'] + $money->summary($user);
            $totalEarnings = $earnings['share_paisa'] / 100;
            $monthly = $earnings['month']['share_paisa'] / 100;
        }

        $data = $insights->build($user) + [
            // the newest four, only what the list shows (never the guests' records or ID numbers)
            'recent_bookings' => $insights->recentBookings($user),
            'total_bookings' => $bookingCount,
            'total_rooms' => $roomCount,
            'booking_counts_by_status' => $bookingCountsByStatus,
            'review_count' => $reviewCount,
            'total_earnings' => $totalEarnings,
            'monthly_revenue' => $monthly,
            'earnings' => $earnings,
            'refunds_waiting' => $user->is_admin ? app(\App\Services\Money\MoneyReport::class)->refundsWaiting() : null,
            'user' => ['id' => $user->id, 'name' => $user->name, 'is_admin' => (bool) $user->is_admin],
        ];

        return $this->sendSuccess($data);
    }
}
