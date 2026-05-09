<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\City;
use App\Models\FacilitySub;
use App\Models\Place;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Room;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FilterController extends BaseController
{
    // ── Nearby search radius in km (default 20km) ─────────
    const DEFAULT_RADIUS_KM = 20;

    // ══════════════════════════════════════════════════════
    //  GET FILTER OPTIONS
    //  GET /api/filters
    // ══════════════════════════════════════════════════════

    public function getFilters(): JsonResponse
    {
        $facilities    = FacilitySub::select('id', 'name')->get();
        $hotelClasses  = ['7 Stars', '6 Stars', '5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star', 'Unrated'];
        $ratings       = [1, 2, 3, 4, 5];
        $propertyTypes = PropertyCategory::select('id', 'name')->get();
        $sortOptions   = [
            ['value' => 'rating',  'name' => 'Top Rated'],
            ['value' => 'asc',     'name' => 'Price: Low to High'],
            ['value' => 'desc',    'name' => 'Price: High to Low'],
            ['value' => 'nearest', 'name' => 'Nearest First'],
        ];

        return $this->sendSuccess([
            'facilities'    => $facilities,
            'hotelClasses'  => $hotelClasses,
            'ratings'       => $ratings,
            'propertyTypes' => $propertyTypes,
            'sortOptions'   => $sortOptions,
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  SEARCH FILTER — /search page
    //  GET /api/filter/search?location=Dhaka&lat=23.8&long=90.4&...
    // ══════════════════════════════════════════════════════

    public function getFilteredProperties(Request $request): JsonResponse
    {
        $params = $request->all();

        $query = $this->buildBaseQuery($params);

        // ── Sort ───────────────────────────────────────────
        $query = $this->applySort($query, $params);

        // ── Execute ────────────────────────────────────────
        $properties = $query
            ->with(['images', 'facilities', 'place.city', 'rooms'])
            ->get()
            ->map(fn($p) => $this->appendComputedFields($p, $params));

        return $this->sendSuccess(['properties' => $properties]);
    }

    // ══════════════════════════════════════════════════════
    //  REQUEST FILTER — /request page
    // ══════════════════════════════════════════════════════

    public function getFilteredPropertiesByRequest(Request $request): JsonResponse
    {
        $params = $request->all();
        $userId = $request->user()->id;

        $query = $this->buildBaseQuery($params);
        $query = $this->applySort($query, $params);

        // Only properties with accepted booking requests for this user
        $query->whereHas('bookingAccepteds', fn($q) =>
        $q->whereHas('bookingRequest', fn($sq) =>
        $sq->where('user_id', $userId)
            ->where('status', 'Approved')
        )
        );

        $properties = $query
            ->with(['images', 'facilities', 'place.city', 'rooms', 'rooms.images', 'bookingAccepteds'])
            ->get()
            ->map(fn($p) => $this->appendComputedFields($p, $params));

        return $this->sendSuccess([
            'properties'               => $properties,
            'accepted_properties_count' => $properties->count(),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — shared query builder
    // ══════════════════════════════════════════════════════

    private function buildBaseQuery(array $params)
    {
        $query = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->whereHas('rooms');

        // ── 1. Geo — nearby radius OR place_id chain ───────
        $lat    = isset($params['lat'])  ? (float) $params['lat']  : null;
        $long   = isset($params['long']) ? (float) $params['long'] : null;
        $radius = isset($params['radius']) ? (float) $params['radius'] : self::DEFAULT_RADIUS_KM;

        if ($lat && $long) {
            // Haversine formula — find properties within radius km
            // Uses properties.lat and properties.long directly
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

            // Add distance as a selected column for sorting
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

        } else {
            // Fall back to place_id or address LIKE
            $placeIds = $this->resolvePlaceIds($params);

            if (!empty($placeIds)) {
                $query->whereIn('place_id', $placeIds);
            } elseif (!empty($params['location'])) {
                // address is serialized — search the raw column value
                $query->whereRaw(
                    'LOWER(address) LIKE ?',
                    ['%' . strtolower(trim($params['location'])) . '%']
                );
            }
        }

        // ── 2. Availability ────────────────────────────────
        if (!empty($params['check_in']) && !empty($params['check_out'])) {
            $checkIn  = $params['check_in'];
            $checkOut = $params['check_out'];

            $query->whereHas('rooms', fn($q) =>
            $q->whereDoesntHave('bookings', fn($b) =>
                // Correct overlap: booking starts before our checkout
                // AND booking ends after our checkin
            $b->where('checkin',  '<', $checkOut)
                ->where('checkout', '>', $checkIn)
            )
            );
        }

        // ── 3. Guest capacity ──────────────────────────────
        if (!empty($params['adult'])) {
            $query->whereHas('rooms', fn($q) =>
            $q->where('guest_capacity', '>=', (int) $params['adult'])
            );
        }

        // ── 4. Facilities (BelongsToMany via property_facilities pivot) ──
        // Property::facilities() → BelongsToMany FacilitySub via property_facilities
        if (!empty($params['facilities'])) {
            $facilityIds = (array) $params['facilities'];
            foreach ($facilityIds as $fid) {
                // Each facility must exist — AND logic (not OR)
                $query->whereHas('facilities', fn($q) =>
                $q->where('facility_subs.id', (int) $fid)
                );
            }
        }

        // ── 5. Hotel class ─────────────────────────────────
        if (!empty($params['hotelClass'])) {
            $query->whereIn('property_class', (array) $params['hotelClass']);
        }

        // ── 6. Rating ──────────────────────────────────────
        // Rating is a decimal — match floor (e.g. "4" matches 4.0–4.99)
        if (!empty($params['rating'])) {
            $ratings = array_map('intval', (array) $params['rating']);
            $query->where(function ($q) use ($ratings) {
                foreach ($ratings as $r) {
                    $q->orWhereBetween('rating', [$r, $r + 0.99]);
                }
            });
        }

        // ── 7. Property type ───────────────────────────────
        if (!empty($params['propertyType'])) {
            $query->whereIn('property_category_id', (array) $params['propertyType']);
        }

        return $query;
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — apply sort
    // ══════════════════════════════════════════════════════

    private function applySort($query, array $params)
    {
        $sortBy  = $params['sortByPrice'] ?? 'rating';
        $hasGeo  = !empty($params['lat']) && !empty($params['long']);

        return match ($sortBy) {
            'nearest' => $hasGeo
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderByDesc('rating'), // fallback if no geo

            'asc'  => $query->orderBy(
                Room::select('base_price')
                    ->whereColumn('rooms.property_id', 'properties.id')
                    ->orderBy('base_price', 'asc')
                    ->limit(1),
                'asc'
            ),

            'desc' => $query->orderBy(
                Room::select('base_price')
                    ->whereColumn('rooms.property_id', 'properties.id')
                    ->orderBy('base_price', 'desc')
                    ->limit(1),
                'desc'
            ),

            default => $query->orderByDesc('rating'), // 'rating' or anything else
        };
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — append min_price + distance to each property
    // ══════════════════════════════════════════════════════

    private function appendComputedFields(Property $property, array $params): Property
    {
        $property->min_price = $property->rooms->min('base_price');

        // Include distance_km if geo search was used
        if (!empty($params['lat']) && !empty($params['long'])) {
            // Already selected via selectRaw — just cast it
            $property->distance_km = round((float) $property->distance_km, 1);
        }

        return $property;
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — resolve place IDs from params
    // ══════════════════════════════════════════════════════

    private function resolvePlaceIds(array $params): array
    {
        // Fast path — SearchBox already resolved the place
        if (!empty($params['place_id'])) {
            $id   = (int) $params['place_id'];
            $type = $params['place_type'] ?? 'place';

            return match ($type) {
                'city'  => Place::where('city_id', $id)->pluck('id')->toArray(),
                'state' => Place::whereHas('city', fn($q) =>
                $q->whereHas('state', fn($s) => $s->where('id', $id))
                )->pluck('id')->toArray(),
                default => [$id],
            };
        }

        if (empty($params['location'])) return [];

        $location = trim($params['location']);
        $placeIds = Place::where('name', 'like', "%{$location}%")->pluck('id')->toArray();

        $cityIds = City::where('name', 'like', "%{$location}%")->pluck('id');
        if ($cityIds->isNotEmpty()) {
            $placeIds = array_merge(
                $placeIds,
                Place::whereIn('city_id', $cityIds)->pluck('id')->toArray()
            );
        }

        $stateIds = State::where('name', 'like', "%{$location}%")->pluck('id');
        if ($stateIds->isNotEmpty()) {
            $stateCityIds = City::whereIn('state_id', $stateIds)->pluck('id');
            $placeIds     = array_merge(
                $placeIds,
                Place::whereIn('city_id', $stateCityIds)->pluck('id')->toArray()
            );
        }

        return array_unique(array_values($placeIds));
    }
}
