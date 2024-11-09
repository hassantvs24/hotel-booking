<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Place;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Traits\MediaMan;
use Exception;
use Illuminate\Http\JsonResponse;

class PropertyController extends BaseController
{
    use MediaMan;
    public function index(): JsonResponse
    {
        $properties = Property::query()
            ->with(['images', 'facilities', 'place.city'])
            ->where('status', Property::STATUS_PUBLISHED)
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

    public function availableRooms(Property $property): JsonResponse
    {
        $rooms = Room::query()
            ->with(['images', 'facilities', 'property'])
            ->where('property_id', $property->id)
            ->where('status', 'Available')
            ->get();
        $data = [
            'rooms' => $rooms
        ];
        return $this->sendSuccess($data);
    }
    public function otherRooms(Property $property): JsonResponse
    {
        $rooms = Room::query()
            ->with(['images', 'facilities', 'property'])
            ->where('property_id', $property->id)
            ->where('status', 'Reserved')
            ->get();
        $data = [
            'rooms' => $rooms
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
