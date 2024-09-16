<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Room;
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

    public function booking(Request $request)
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
            return response()->json(['success' => true, 'data' => $booking], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
