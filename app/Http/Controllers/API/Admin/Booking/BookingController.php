<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Repositories\Admin\BookingRepository;
use App\Services\Booking\PaidBookingGuard;
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

        // Merchant scope — only bookings for their properties' rooms
        if ($user->is_merchant && !$user->is_admin) {
            $propertyIds = $user->properties()->pluck('id');
            $query->whereHas('room', function ($q) use ($propertyIds) {
                $q->whereIn('property_id', $propertyIds);
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
        ])->whereIn('id', $this->visible()->select('id'))->findOrFail($id);

        return $this->sendSuccess($booking);
    }

    public function update(Request $request, BookingRepository $repo, $id): JsonResponse
    {
        try {
            $booking = $this->visible()->findOrFail($id);

            $data = $request->validate([
                'adult'    => 'sometimes|integer|min:1',
                'children' => 'sometimes|integer|min:0',
                'room_id'  => 'sometimes|exists:rooms,id',
                'checkin'  => 'sometimes|date',
                'checkout' => 'sometimes|date|after:checkin',
            ]);

            // a vendor can only move a booking to a room of their own
            if (isset($data['room_id']) && $this->isVendor() && !Room::where('id', $data['room_id'])->whereIn('property_id', auth()->user()->properties()->pluck('id'))->exists()) {
                return $this->sendError('You can only choose a room of your own property.', [], 422);
            }

            // once the guest has paid, the room and dates are fixed: money and refund rules depend on them
            if ($why = app(PaidBookingGuard::class)->whyNotChange($booking, $data)) {
                return $this->sendError($why, [], 422);
            }

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
            $booking = $this->visible()->findOrFail($id);

            if ($why = app(PaidBookingGuard::class)->whyNotDelete($booking)) {
                return $this->sendError($why, [], 422);
            }

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

            $booking = $this->visible()->findOrFail($id);

            if ($why = app(PaidBookingGuard::class)->whyNotStatus($booking, $request->input('status'))) {
                return $this->sendError($why, [], 422);
            }

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
        $user  = auth()->user();
        $query = Booking::query();

        // Merchant scope — only bookings for their properties' rooms
        if ($user->is_merchant && !$user->is_admin) {
            $propertyIds = $user->properties()->pluck('id');
            $query->whereHas('room', function ($q) use ($propertyIds) {
                $q->whereIn('property_id', $propertyIds);
            });
        }

        $stats = [
            'total'     => (clone $query)->count(),
            'pending'   => (clone $query)->where('status', 'pending')->count(),
            'reserved'  => (clone $query)->where('status', 'reserved')->count(),
            'approved'  => (clone $query)->where('status', 'approved')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
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
        $bookedRoomIds = array_merge(
            Booking::CheckDateOverlap($roomIds, $checkIn, $checkOut)->pluck('room_id')->toArray(),
            \App\Models\RoomRequest::heldRoomIds($checkIn, $checkOut, $roomIds)   // held for a guest whose offer was accepted
        );
        $availableRooms = Room::where('property_id', $property->id)
            ->whereNotIn('id', $bookedRoomIds)
            ->get(['id', 'name']);

        return $this->sendSuccess(['rooms' => $availableRooms]);
    }

    /**
     * The name and the ID (NID or passport) of the person who will stay. Only the admin and the owner of the booked property ask for
     * it, one booking at a time; it never travels inside the booking lists. Every look is written to the log.
     */
    public function guestId($id): JsonResponse
    {
        $booking = $this->visible()->findOrFail($id);

        \Illuminate\Support\Facades\Log::info('Guest ID looked at', ['booking' => $booking->booking_number, 'by' => auth()->id()]);

        $number = $booking->main_guest_id_number;

        return $this->sendSuccess([
            'provided' => filled($number),
            'name' => $booking->main_guest_name,
            'type' => $booking->main_guest_id_type,
            'number' => $number,
            'masked' => $number ? \App\Services\Booking\GuestIdentity::mask($number) : null,
        ]);
    }

    private function isVendor(): bool
    {
        $user = auth()->user();

        return $user->is_merchant && !$user->is_admin;
    }

    /** The bookings this person may touch: all of them for the admin, only the bookings of their own properties for a vendor */
    private function visible()
    {
        $query = Booking::query();

        if ($this->isVendor()) {
            $propertyIds = auth()->user()->properties()->pluck('id');
            $query->whereHas('room', fn ($q) => $q->whereIn('property_id', $propertyIds));
        }

        return $query;
    }
}
