<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\Facility;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Room;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends BaseController
{
    public function getFilters(): JsonResponse
    {

        $facilities = Facility::get();
        $hotelClasses = ['7 Stars', '6 Stars', '5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star', 'Unrated'];
        $ratings = [1, 2, 3, 4, 5];
        $propertyTypes = PropertyCategory::all();
        $sortOptions = [
            ['value' => 'asc', 'name' => 'Low to High'],
            ['value' => 'desc', 'name' => 'High to Low'],
        ];


        $data = [
            'facilities' => $facilities,
            'hotelClasses' => $hotelClasses,
            'ratings' => $ratings,
            'propertyTypes' => $propertyTypes,
            'sortOptions' => $sortOptions,
        ];

        return $this->sendSuccess($data);
    }

    public function getFilteredProperties(Request $request): JsonResponse
    {
        $params = $request->input('params', []);
        $propertiesQuery = Property::query();

        if (!empty($params['location'])) {
            $location = trim($params['location']);
            $propertiesQuery->whereRaw('LOWER(address) LIKE ?', ['%' . strtolower($location) . '%']);
        }

        $properties = $propertiesQuery->where('status', Property::STATUS_PUBLISHED)
            ->with(['images', 'facilities', 'place.city', 'rooms'])
            ->get();

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data);
    }

    public function getFilteredPropertiesByRequest(Request $request): JsonResponse
    {
        // Retrieve parameters from the request or set an empty array if none provided
        $params = $request->input('params', []);
        $userId = $request->user()->id;
        // Start building the query for Property
        $propertiesQuery = Property::query();

        // Optional filter based on location or address, if provided
        if (!empty($params['location'])) {
            $location = trim($params['location']);
            $propertiesQuery->whereRaw('LOWER(address) LIKE ?', ['%' . strtolower($location) . '%']);
        }

        // Filter to ensure properties have accepted bookings for the authenticated user
        $properties = $propertiesQuery->whereHas('bookingAccepteds', function ($query) use ($userId) {
            $query->whereHas('bookingRequest', function ($subQuery) use ($userId) {
                $subQuery->where('user_id', $userId)
                    ->whereIn('status', ['Approved']);
            });
        })
            ->with([
                'images',
                'facilities',
                'place.city',
                'rooms',
                'rooms.images',
                'bookingAccepteds'
            ])
            ->get();


        $propertyCount = $properties->count();

        // Prepare and format the response data
        $data = [
            'properties' => $properties,
            'accepted_properties_count' => $propertyCount,
        ];

        // Send a successful response
        return $this->sendSuccess($data);
    }
}
