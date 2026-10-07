<?php

namespace App\Http\Controllers\API\Admin\Dashboard;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Room;
use App\Models\Transaction;
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

        $bookings = $scoped(Booking::with(['room', 'room.property', 'room.property.place.city', 'user', 'user.profile']))
            ->latest()->take(3)->get();
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

        // Total earnings — same definition used on the Transactions page:
        // completed transactions' full amount, plus the cancellation fee
        // kept from any completed refund (the transaction itself moves to
        // "refunded" status, but that fee is still money the property keeps).
        $transactionScope = fn($query) => $propertyIds === null
            ? $query
            : $query->whereHas('booking.room', fn($q) => $q->whereIn('property_id', $propertyIds));
        $refundScope = fn($query) => $propertyIds === null
            ? $query
            : $query->whereHas('booking.room', fn($q) => $q->whereIn('property_id', $propertyIds));

        $completedRevenue = $transactionScope(Transaction::where('status', 'completed'))->sum('amount');
        $keptFromRefunds = $refundScope(RefundRequest::where('status', RefundRequest::STATUS_COMPLETED))->sum('cancellation_fee');
        $totalEarnings = (float) $completedRevenue + (float) $keptFromRefunds;

        $data = [
            'bookings' => $bookings,
            'total_bookings' => $bookingCount,
            'total_rooms' => $roomCount,
            'booking_counts_by_status' => $bookingCountsByStatus,
            'review_count' => $reviewCount,
            'total_earnings' => $totalEarnings,
            'user' => $user
        ];

        return $this->sendSuccess($data);
    }
}
