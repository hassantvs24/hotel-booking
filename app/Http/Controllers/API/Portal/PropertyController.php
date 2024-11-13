<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Booking;
use App\Models\Place;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends BaseController
{
    use MediaMan;
    public function index(): JsonResponse
    {
        $properties = Property::query()
            ->whereHas('rooms')
            ->where('status', Property::STATUS_PUBLISHED)
            ->with(['images', 'facilities', 'place.city'])
            ->get();

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data);
    }


    public function placeWiseProperties(Place $place): JsonResponse
    {
        $properties = Property::query()
            ->with(['images', 'facilities', 'place.city'])
            ->where([
                'place_id' => $place->id,
                'status'   => Property::STATUS_PUBLISHED,
            ])
            ->get();

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data);
    }

    public function details(Property $property): JsonResponse
    {
        $property->load([
            'images',
            'facilities',
            'rooms.images',
            'rooms.facilities',
            'place.city'
        ]);

        $data = [
            'property' => $property
        ];

        return $this->sendSuccess($data);
    }

    public function availableRooms(Request $request, $propertyId): JsonResponse
    {
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');

        $roomIds = Room::where('property_id', $propertyId)->pluck('id')->toArray();

        $bookedRoomIds = array_merge(
            Booking::CheckDateOverlap($roomIds, $checkIn, $checkOut)->pluck('room_id')->toArray()
        );
        $availableRooms = Room::where('property_id', $propertyId)
            ->whereNotIn('id', $bookedRoomIds)
            ->with(['images', 'facilities', 'property'])
            ->get();

        $data = [
            'rooms' => $availableRooms
        ];

        return $this->sendSuccess($data);
    }


    public function otherRooms(Request $request, $propertyId): JsonResponse
    {
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');

        $roomIds = Room::where('property_id', $propertyId)->pluck('id')->toArray();

        $bookedRoomIds = array_merge(
            Booking::CheckDateOverlap($roomIds, $checkIn, $checkOut)->pluck('room_id')->toArray()
        );
        $bookedRooms = Room::where('property_id', $propertyId)
            ->whereIn('id', $bookedRoomIds)
            ->with(['images', 'facilities', 'property'])
            ->get();

        $data = [
            'rooms' => $bookedRooms
        ];

        return $this->sendSuccess($data);
    }


    public function bookingRoomCheck($property): JsonResponse
    {

        $data = RoomRequest::where('property_id', $property)->exists();

        return $this->sendSuccess($data);
    }

    public function checkBookedDate($propertyId): JsonResponse
    {
        try {
            $bookedDates = [];
            $rooms = Room::where('property_id', $propertyId)->get();
            foreach ($rooms as $room) {
                $bookedDates[] = $room->booked_date;
                $bookedDates[] = $room->booked_off_date;
            }



            return response()->json($bookedDates);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
