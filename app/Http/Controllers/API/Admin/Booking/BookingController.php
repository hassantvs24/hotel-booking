<?php

namespace App\Http\Controllers\API\Admin\Booking;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Repositories\Admin\BookingRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BookingController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->is_admin) {
            $bookings = Booking::with(['room', 'room.property', 'room.property.place.city', 'user', 'user.profile'])->paginate();
        } elseif ($user->is_merchant && $user->associated_property) {
            $bookings = Booking::whereHas('room', function ($query) use ($request) {
                $query->where('property_id', $request->user()->associated_property->id);
            })->with(['room', 'room.property', 'room.property.place', 'user', 'user.profile'])->paginate();
        }

        $data = [
            'bookings' => $bookings
        ];

        return $this->sendSuccess($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BookingRepository $bookingRepository, $bookingId): JsonResponse
    {
        try {
            $bookingId = $bookingRepository->getModel($bookingId);
            $request = $request->all();
            $booking = $bookingRepository->update([
                'adult' => $request['adult'],
                'children' => $request['children'],
                'room_id' => $request['room_id'],
                'checkin' => $request['check_in'],
                'checkout' => $request['check_out'],
            ], $bookingId);

            $room = Room::find($request['room_id']);
            if ($room) {
                $room->booked_date = $request['checkin'];
                $room->booked_off_date = $request['checkout'];
                $room->status = 'Booked';
                $room->save();
            }
            return $this->sendSuccess($booking);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }





    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingRepository $bookingRepository, $bookingId)
    {
        try {
            $booking = $bookingRepository->getModel($bookingId);
            $room = Room::find($booking->room_id);
            if ($room) {
                $room->update([
                    'status' => 'Available',
                    'booked_date' => null,
                    'booked_off_date' => null
                ]);
            }
            $bookingRepository->delete($booking->id);
            return $this->sendSuccess('Booking deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|string'
            ]);

            // Fetch the booking by its ID
            $booking = Booking::findOrFail($id);

            // Update the status
            $booking->update([
                'status' => $validatedData['status']
            ]);

            return $this->sendSuccess($booking);
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
    }

    public function bookingCheck(Request $request): JsonResponse
    {
        $checkIn = $request->input('params.check_in');
        $checkOut = $request->input('params.check_out');


        $checkInDate = Carbon::parse($checkIn)->toDateString();
        $checkOutDate = Carbon::parse($checkOut)->toDateString();

        $property = $request->user()->associated_property;
        if (!$property) {
            return $this->sendError('User does not have an associated property.');
        }

        $roomIds = Room::where('property_id', $property->id)->pluck('id')->toArray();
        $bookedRoomIds = Booking::CheckDateOverlap($roomIds, $checkInDate, $checkOutDate)
            ->pluck('room_id')
            ->toArray();

        $availableRooms = Room::where('property_id', $property->id)
            ->whereNotIn('id', $bookedRoomIds)
            ->get(['id', 'name']);

        return $this->sendSuccess([
            'rooms' => $availableRooms
        ]);
    }
}
