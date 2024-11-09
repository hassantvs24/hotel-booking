<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomRequest;
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
        $booking = Booking::where('user_id', $request->user()->id)
            ->where('room_id', $room)
            ->first();
        if ($booking) {
            $existBooking = true;
        } else {
            $existBooking = false;
        }
        $data = [
            'existBooking' => $existBooking
        ];
        return $this->sendSuccess($data);
    }


    public function cartList(Request $request): JsonResponse
    {
        $roomRequest = RoomRequest::where('user_id', $request->user()->id)
            ->with([
                'room',
                'room.property',
                'room.primaryImage',
                'room.facilities',
                'room.property.place.city'
            ])->get();

        $bookingList = Booking::where('user_id', $request->user()->id)
            ->with(['room', 'room.property', 'user', 'user.profile', 'room.property.place'])
            ->get();
        $data = [
            'roomRequest' => $roomRequest,
            'bookingList' => $bookingList,
        ];
        return $this->sendSuccess($data);
    }
}
