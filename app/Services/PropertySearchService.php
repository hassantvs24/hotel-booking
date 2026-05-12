<?php

namespace App\Services;

use App\Models\City;
use App\Models\Place;
use App\Models\Property;
use App\Models\Room;
use App\Models\State;

class PropertySearchService
{
    const DEFAULT_RADIUS_KM = 20;

    public function search(array $params)
    {
        $query = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->whereHas('rooms');

        $this->applyLocation($query, $params);
        $this->applyAvailability($query, $params);
        $this->applyGuestCapacity($query, $params);
        $this->applyFacilities($query, $params);
        $this->applyHotelClass($query, $params);
        $this->applyRating($query, $params);
        $this->applyPropertyType($query, $params);
        $this->applySort($query, $params);

        return $query
            ->with([
                'images',
                'facilities',
                'place.city.state',
                'rooms'
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->map(function ($property) use ($params) {

                $property->min_price =
                    $property->rooms->min('base_price');

                if (
                    !empty($params['lat']) &&
                    !empty($params['long']) &&
                    isset($property->distance_km)
                ) {
                    $property->distance_km =
                        round((float) $property->distance_km, 1);
                }

                return $property;
            });
    }

    // =====================================================
    // LOCATION
    // =====================================================

    private function applyLocation($query, array $params): void
    {
        $lat    = $params['lat'] ?? null;
        $long   = $params['long'] ?? null;
        $radius = $params['radius'] ?? self::DEFAULT_RADIUS_KM;

        if ($lat && $long) {

            $query->whereRaw("
                (
                    6371 * acos(
                        cos(radians(?))
                        * cos(radians(lat))
                        * cos(radians(`long`) - radians(?))
                        + sin(radians(?))
                        * sin(radians(lat))
                    )
                ) <= ?
            ", [$lat, $long, $lat, $radius]);

            $query->selectRaw("
                properties.*,
                (
                    6371 * acos(
                        cos(radians(?))
                        * cos(radians(lat))
                        * cos(radians(`long`) - radians(?))
                        + sin(radians(?))
                        * sin(radians(lat))
                    )
                ) AS distance_km
            ", [$lat, $long, $lat]);

            return;
        }

        $placeIds = $this->resolvePlaceIds($params);

        if (!empty($placeIds)) {

            $query->whereIn('place_id', $placeIds);

        } elseif (!empty($params['location'])) {

            $query->whereRaw(
                'LOWER(address) LIKE ?',
                ['%' . strtolower(trim($params['location'])) . '%']
            );
        }
    }

    // =====================================================
    // AVAILABILITY
    // =====================================================

    private function applyAvailability($query, array $params): void
    {
        if (
            empty($params['check_in']) ||
            empty($params['check_out'])
        ) {
            return;
        }

        $checkIn  = $params['check_in'];
        $checkOut = $params['check_out'];

        $query->whereHas('rooms', function ($q) use ($checkIn, $checkOut) {

            $q->whereDoesntHave('bookings', function ($b)
            use ($checkIn, $checkOut) {

                $b->where('checkin', '<', $checkOut)
                    ->where('checkout', '>', $checkIn);
            });
        });
    }

    // =====================================================
    // GUEST
    // =====================================================

    private function applyGuestCapacity($query, array $params): void
    {
        if (empty($params['adult'])) {
            return;
        }

        $adult = (int) $params['adult'];

        $query->whereHas('rooms', function ($q) use ($adult) {

            $q->where('guest_capacity', '>=', $adult);
        });
    }

    // =====================================================
    // FACILITIES
    // =====================================================

    private function applyFacilities($query, array $params): void
    {
        if (empty($params['facilities'])) {
            return;
        }

        foreach ((array) $params['facilities'] as $fid) {

            $query->whereHas('facilities', function ($q) use ($fid) {

                $q->where('facility_subs.id', (int) $fid);
            });
        }
    }

    // =====================================================
    // HOTEL CLASS
    // =====================================================

    private function applyHotelClass($query, array $params): void
    {
        if (empty($params['hotelClass'])) {
            return;
        }

        $query->whereIn(
            'property_class',
            (array) $params['hotelClass']
        );
    }

    // =====================================================
    // RATING
    // =====================================================

    private function applyRating($query, array $params): void
    {
        if (empty($params['rating'])) {
            return;
        }

        $ratings = array_map(
            'intval',
            (array) $params['rating']
        );

        $query->where(function ($q) use ($ratings) {

            foreach ($ratings as $r) {

                $q->orWhereBetween(
                    'rating',
                    [$r, $r + 0.99]
                );
            }
        });
    }

    // =====================================================
    // PROPERTY TYPE
    // =====================================================

    private function applyPropertyType($query, array $params): void
    {
        if (empty($params['propertyType'])) {
            return;
        }

        $query->whereIn(
            'property_category_id',
            (array) $params['propertyType']
        );
    }

    // =====================================================
    // SORT
    // =====================================================

    private function applySort($query, array $params): void
    {
        $sortBy = $params['sortByPrice'] ?? 'rating';

        $hasGeo = !empty($params['lat'])
            && !empty($params['long']);

        match ($sortBy) {

            'nearest' => $hasGeo
                ? $query->orderBy('distance_km')
                : $query->orderByDesc('rating'),

            'asc' => $query->orderBy(
                Room::select('base_price')
                    ->whereColumn(
                        'rooms.property_id',
                        'properties.id'
                    )
                    ->orderBy('base_price', 'asc')
                    ->limit(1),
                'asc'
            ),

            'desc' => $query->orderBy(
                Room::select('base_price')
                    ->whereColumn(
                        'rooms.property_id',
                        'properties.id'
                    )
                    ->orderBy('base_price', 'desc')
                    ->limit(1),
                'desc'
            ),

            default => $query->orderByDesc('rating')
        };
    }

    // =====================================================
    // RESOLVE PLACE IDS
    // =====================================================

    private function resolvePlaceIds(array $params): array
    {
        if (!empty($params['place_id'])) {

            $id   = (int) $params['place_id'];
            $type = $params['place_type'] ?? 'place';

            return match ($type) {

                'city' => Place::where('city_id', $id)
                    ->pluck('id')
                    ->toArray(),

                'state' => Place::whereHas('city', function ($q)
                use ($id) {

                    $q->whereHas('state', function ($s)
                    use ($id) {

                        $s->where('id', $id);
                    });

                })->pluck('id')->toArray(),

                default => [$id],
            };
        }

        if (empty($params['location'])) {
            return [];
        }

        $location = trim($params['location']);

        $placeIds = Place::where(
            'name',
            'like',
            "%{$location}%"
        )->pluck('id')->toArray();

        $cityIds = City::where(
            'name',
            'like',
            "%{$location}%"
        )->pluck('id');

        if ($cityIds->isNotEmpty()) {

            $placeIds = array_merge(
                $placeIds,
                Place::whereIn('city_id', $cityIds)
                    ->pluck('id')
                    ->toArray()
            );
        }

        $stateIds = State::where(
            'name',
            'like',
            "%{$location}%"
        )->pluck('id');

        if ($stateIds->isNotEmpty()) {

            $stateCityIds = City::whereIn(
                'state_id',
                $stateIds
            )->pluck('id');

            $placeIds = array_merge(
                $placeIds,
                Place::whereIn('city_id', $stateCityIds)
                    ->pluck('id')
                    ->toArray()
            );
        }

        return array_unique(array_values($placeIds));
    }
}
