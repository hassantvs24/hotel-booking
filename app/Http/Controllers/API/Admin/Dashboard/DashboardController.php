<?php

namespace App\Http\Controllers\API\Admin\Dashboard;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->is_admin) {
            $bookings = Booking::with(['room', 'room.property', 'room.property.place.city', 'user', 'user.profile'])->latest()->take(3)->get();
        } elseif ($user->is_merchant && $user->associated_property) {
            $bookings = Booking::whereHas('room', function ($query) use ($request) {
                $query->where('property_id', $request->user()->associated_property->id);
            })->with(['room', 'room.property', 'room.property.place', 'user'])->latest()->take(3)->get();
        }

        $bookingCount = Booking::count();
        $roomCount = Room::count();
        $reviewCount = Review::count();
        $bookingCountsByStatus = Booking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $data = [
            'bookings' => $bookings,
            'total_bookings' => $bookingCount,
            'total_rooms' => $roomCount,
            'booking_counts_by_status' => $bookingCountsByStatus,
            'review_count' => $reviewCount,
            'user' => $user
        ];

        return $this->sendSuccess($data);
    }
}
