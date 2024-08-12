<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Place;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class PropertyController extends BaseController
{
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
            ->with(['images', 'facilities'])
            ->where('property_id', $property->id)
            ->get();
        $data = [
            'rooms' => $rooms
        ];

        return $this->sendSuccess($data);
    }
}
