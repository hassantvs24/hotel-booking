<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Repositories\Admin\BookingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends BaseController
{
    public function index(Request $request, BookingRepository $repo): JsonResponse
    {
        $user = auth()->user();

        // Build base query with eager loads
        $query = Booking::with([
            'room',
            'room.property',
            'room.property.place.city',
            'room.primaryImage',
            'user',
            'transaction',
        ]);

        // Merchant scope
        if ($user->is_merchant && !$user->is_admin) {
            $query->whereHas('room', function ($q) use ($user) {
                $q->where('property_id', $user->associated_property->id);
            });
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('room', fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->orderBy('id', 'DESC')
            ->paginate($request->input('per_page', 15), ['*'], 'page', $request->input('page', 1));

        return $this->sendSuccess(['bookings' => $bookings]);
    }

    public function show($id): JsonResponse
    {
        $booking = Booking::with([
            'room',
            'room.property',
            'room.property.place.city',
            'room.primaryImage',
            'room.facilities',
            'user',
            'transaction',
        ])->findOrFail($id);

        return $this->sendSuccess($booking);
    }

    public function update(Request $request, BookingRepository $repo, $id): JsonResponse
    {
        try {
            $booking = $repo->getModel($id);

            $data = $request->validate([
                'adult'    => 'sometimes|integer|min:1',
                'children' => 'sometimes|integer|min:0',
                'room_id'  => 'sometimes|exists:rooms,id',
                'checkin'  => 'sometimes|date',
                'checkout' => 'sometimes|date|after:checkin',
            ]);

            $booking = $repo->update($data, $booking);

            // Update room dates if room/dates changed
            if (isset($data['room_id'])) {
                Room::where('id', $data['room_id'])->update([
                    'booked_date'     => $data['checkin']  ?? $booking->checkin,
                    'booked_off_date' => $data['checkout'] ?? $booking->checkout,
                    'status'          => 'Booked',
                ]);
            }

            return $this->sendSuccess($booking->fresh(['room', 'user', 'transaction']));
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function destroy(BookingRepository $repo, $id): JsonResponse
    {
        try {
            $booking = $repo->getModel($id);

            // Free the room
            Room::where('id', $booking->room_id)->update([
                'status'          => 'Available',
                'booked_date'     => null,
                'booked_off_date' => null,
            ]);

            $repo->delete($booking->id);

            return $this->sendSuccess(null, 'Booking deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|string|in:pending,reserved,approved,cancelled,refunded,completed',
            ]);

            $booking = Booking::findOrFail($id);
            $booking->update(['status' => $request->input('status')]);

            // Free room if cancelled/refunded
            if (in_array($request->input('status'), ['cancelled', 'refunded'])) {
                Room::where('id', $booking->room_id)->update([
                    'status'          => 'Available',
                    'booked_date'     => null,
                    'booked_off_date' => null,
                ]);
            }

            return $this->sendSuccess($booking->fresh(['room', 'user', 'transaction']));
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total'     => Booking::count(),
            'pending'   => Booking::where('status', 'pending')->count(),
            'reserved'  => Booking::where('status', 'reserved')->count(),
            'approved'  => Booking::where('status', 'approved')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
        ];

        return $this->sendSuccess($stats);
    }

    public function bookingCheck(Request $request): JsonResponse
    {
        $checkIn  = $request->input('params.check_in');
        $checkOut = $request->input('params.check_out');

        $property = $request->user()->associated_property;
        if (!$property) {
            return $this->sendError('No associated property found.');
        }

        $roomIds       = Room::where('property_id', $property->id)->pluck('id')->toArray();
        $bookedRoomIds = Booking::CheckDateOverlap($roomIds, $checkIn, $checkOut)->pluck('room_id')->toArray();
        $availableRooms = Room::where('property_id', $property->id)
            ->whereNotIn('id', $bookedRoomIds)
            ->get(['id', 'name']);

        return $this->sendSuccess(['rooms' => $availableRooms]);
    }
}
