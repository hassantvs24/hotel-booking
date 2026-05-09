<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\FacilitySub;
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

        $facilities = FacilitySub::get();
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

    // :TODO: Optimize the query and filter logic for better performance and scalability

    public function getFilteredProperties(Request $request): JsonResponse
    {
        $params = $request->input('params', []);
        $propertiesQuery = Property::query();

        if (!empty($params['location'])) {
            $location = trim($params['location']);
            $propertiesQuery->whereRaw('LOWER(address) LIKE ?', ['%' . strtolower($location) . '%']);
        }


        if (!empty($params['check_in']) && !empty($params['check_out'])) {
            $checkIn = $params['check_in'];
            $checkOut = $params['check_out'];

            $propertiesQuery->whereHas('rooms', function ($query) use ($checkIn, $checkOut) {
                $query->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('checkin', [$checkIn, $checkOut])
                        ->orWhereBetween('checkout', [$checkIn, $checkOut])
                        ->orWhere(function ($q) use ($checkIn, $checkOut) {
                            $q->where('checkin', '<=', $checkIn)
                                ->where('checkout', '>=', $checkOut);
                        });
                });
            });
        }
        if (!empty($params['adult']) || !empty($params['child'])) {
            $adultCount = $params['adult'] ?? 0;
            $childCount = $params['child'] ?? 0;

            $propertiesQuery->whereHas('rooms', function ($query) use ($adultCount, $childCount) {
                $query->where('guest_capacity', '>=', $adultCount)
                    ->where('guest_capacity', '>=', $childCount);
            });
        }
        // if (!empty($params['room'])) {
        //     $roomCount = $params['room'];
        //     $propertiesQuery->whereHas('rooms', function ($query) use ($roomCount) {
        //         // Use COUNT and group by property_id
        //         $query->selectRaw('COUNT(rooms.id) as room_count')
        //             ->groupBy('rooms.property_id') // Group by property to count rooms
        //             ->havingRaw('COUNT(rooms.id) >= ?', [$roomCount]);
        //     });
        // }

        if (!empty($params['facilities'])) {
            $facilities = $params['facilities'];
            $propertiesQuery->whereHas('facilities', function ($query) use ($facilities) {
                $query->whereIn('name', $facilities);
            });
        }

        if (!empty($params['hotelClass'])) {
            $hotelClasses = $params['hotelClass'];
            $propertiesQuery->whereIn('property_class', $hotelClasses);
        }

        // // Filter by rating
        if (!empty($params['rating'])) {
            $ratings = $params['rating'];
            $propertiesQuery->whereIn('rating', $ratings);
        }

        // // Filter by property type
        if (!empty($params['propertyType'])) {
            $propertyTypes = $params['propertyType'];
            $propertiesQuery->whereIn('property_category_id', $propertyTypes);
        }


        if (!empty($params['sortByPrice'])) {
            $sortOrder = strtolower($params['sortByPrice']) === 'desc' ? 'desc' : 'asc';

            $propertiesQuery->with(['images', 'facilities', 'place.city', 'rooms' => function ($query) use ($sortOrder) {
                $query->orderBy('base_price', $sortOrder);
            }])
                ->orderBy(
                    Room::select('base_price')
                        ->whereColumn('rooms.property_id', 'properties.id')
                        ->orderBy('base_price', $sortOrder)
                        ->limit(1),
                    $sortOrder
                );
        }


        $propertiesQuery->whereHas('rooms');

        $properties = $propertiesQuery->where('status', Property::STATUS_PUBLISHED)
            ->with(['images', 'facilities', 'place.city', 'rooms'])
            ->get();

        $data = [
            'properties' => Property::get()
        ];

        return $this->sendSuccess($data);
    }





    public function getFilteredPropertiesByRequest(Request $request): JsonResponse
    {
        $params = $request->input('params', []);
        $userId = $request->user()->id;

        $propertiesQuery = Property::query();

        if (!empty($params['location'])) {
            $location = trim($params['location']);
            $propertiesQuery->whereRaw('LOWER(address) LIKE ?', ['%' . strtolower($location) . '%']);
        }

        if (!empty($params['check_in']) && !empty($params['check_out'])) {
            $checkIn = $params['check_in'];
            $checkOut = $params['check_out'];

            $propertiesQuery->whereHas('rooms', function ($query) use ($checkIn, $checkOut) {
                $query->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('checkin', [$checkIn, $checkOut])
                        ->orWhereBetween('checkout', [$checkIn, $checkOut])
                        ->orWhere(function ($q) use ($checkIn, $checkOut) {
                            $q->where('checkin', '<=', $checkIn)
                                ->where('checkout', '>=', $checkOut);
                        });
                });
            });
        }
        if (!empty($params['adult']) || !empty($params['child'])) {
            $adultCount = $params['adult'] ?? 0;
            $childCount = $params['child'] ?? 0;

            $propertiesQuery->whereHas('rooms', function ($query) use ($adultCount, $childCount) {
                $query->where('guest_capacity', '>=', $adultCount)
                    ->where('guest_capacity', '>=', $childCount);
            });
        }
        // if (!empty($params['room'])) {
        //     $roomCount = $params['room'];
        //     $propertiesQuery->whereHas('rooms', function ($query) use ($roomCount) {
        //         // Use COUNT and group by property_id
        //         $query->selectRaw('COUNT(rooms.id) as room_count')
        //             ->groupBy('rooms.property_id') // Group by property to count rooms
        //             ->havingRaw('COUNT(rooms.id) >= ?', [$roomCount]);
        //     });
        // }

        if (!empty($params['facilities'])) {
            $facilities = $params['facilities'];
            $propertiesQuery->whereHas('facilities', function ($query) use ($facilities) {
                $query->whereIn('name', $facilities);
            });
        }

        if (!empty($params['hotelClass'])) {
            $hotelClasses = $params['hotelClass'];
            $propertiesQuery->whereIn('property_class', $hotelClasses);
        }

        // // Filter by rating
        if (!empty($params['rating'])) {
            $ratings = $params['rating'];
            $propertiesQuery->whereIn('rating', $ratings);
        }

        // // Filter by property type
        if (!empty($params['propertyType'])) {
            $propertyTypes = $params['propertyType'];
            $propertiesQuery->whereIn('property_category_id', $propertyTypes);
        }


        if (!empty($params['sortByPrice'])) {
            $sortOrder = strtolower($params['sortByPrice']) === 'desc' ? 'desc' : 'asc';

            $propertiesQuery->with(['images', 'facilities', 'place.city', 'rooms' => function ($query) use ($sortOrder) {
                $query->orderBy('base_price', $sortOrder);
            }])
                ->orderBy(
                    Room::select('base_price')
                        ->whereColumn('rooms.property_id', 'properties.id')
                        ->orderBy('base_price', $sortOrder)
                        ->limit(1),
                    $sortOrder
                );
        }



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

        $data = [
            'properties' => $properties,
            'accepted_properties_count' => $propertyCount,
        ];

        return $this->sendSuccess($data);
    }
}
