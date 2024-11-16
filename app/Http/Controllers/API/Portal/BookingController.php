<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends BaseController
{
    public function paymentDetails(Room $room): JsonResponse
    {
        $room->load([
            'images',
            'property',
            'property.place.city'
        ]);

        $data = [
            'room' => $room
        ];

        return $this->sendSuccess($data);
    }

    public function bookingStore(Request $request)
    {
        $validated = $request->validate([
            'booking_number' => 'required|string',
            'checkin' => 'required|date',
            'checkout' => 'required|date',
            'adult' => 'required|integer',
            'children' => 'required|integer',
            'rooms' => 'required|integer',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
            'room_id' => 'required|exists:rooms,id',
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $booking = Booking::create($validated);
            $room = Room::find($validated['room_id']);
            if ($room) {
                $room->booked_date = $validated['checkin'];
                $room->booked_off_date = $validated['checkout'];
                $room->status = 'Booked';
                $room->save();
            }
            return response()->json(['success' => true, 'data' => $booking], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function bookingCheck(Request $request, $room): JsonResponse
    {

        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
        ]);

        $checkIn = Carbon::parse($request->input('check_in'))->format('Y-m-d');
        $checkOut = Carbon::parse($request->input('check_out'))->format('Y-m-d');

        $user = auth()->user();

        $roomIds = Room::where('id', $room)->pluck('id');

        $bookedRoomIds = Booking::where('user_id', $user->id)
            ->whereIn('room_id', $roomIds)
            ->CheckDateOverlap($roomIds, $checkIn, $checkOut)
            ->pluck('room_id');

        $isBooked = $bookedRoomIds->isNotEmpty();

        // Return the result
        return $this->sendSuccess([
            'existBooking' => $isBooked
        ]);
    }

    public function cartList(Request $request): JsonResponse
    {
        $roomRequest = RoomRequest::where('user_id', $request->user()->id)
            ->with([
                'room',
                'user',
                'user.profile',
                'room.property',
                'room.property.logoImage',
                'room.primaryImage',
                'room.facilities',
                'room.property.place.city'
            ])->get();

        $bookingList = Booking::where('user_id', $request->user()->id)
            ->with(['room', 'room.roomType', 'room.property', 'user', 'user.profile', 'room.property.place'])
            ->get();
        $data = [
            'roomRequest' => $roomRequest,
            'bookingList' => $bookingList,
        ];
        return $this->sendSuccess($data);
    }
}
