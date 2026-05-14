<?php

namespace App\Services;

use App\Models\FacilitySub;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * PropertySearchService
 *
 * Handles ALL property query concerns:
 *   - Building the base query (geo, availability, filters)
 *   - Applying sort
 *   - Appending computed fields (min_price, distance_km)
 *   - Filter options for dropdowns
 *
 * Injects LocationService for geo resolution.
 * Knows nothing about Nominatim or HTTP — that's LocationService's job.
 *
 * Used by:
 *   - SearchController  → search()
 *   - FilterController  → getFilteredProperties(), getFilteredPropertiesByRequest()
 */
class PropertySearchService
{
    const DEFAULT_RADIUS_KM = 20;

    public function __construct(
        protected LocationService $locationService
    ) {}

    // ══════════════════════════════════════════════════════
    //  FILTER OPTIONS  (FilterController → getFilters)
    // ══════════════════════════════════════════════════════

    public function getFilterOptions(): array
    {
        return [
            'facilities'    => FacilitySub::select('id', 'name')->get(),
            'hotelClasses'  => ['7 Stars', '6 Stars', '5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star', 'Unrated'],
            'ratings'       => [1, 2, 3, 4, 5],
            'propertyTypes' => PropertyCategory::select('id', 'name')->get(),
            'sortOptions'   => [
                ['value' => 'rating',  'name' => 'Top Rated'],
                ['value' => 'asc',     'name' => 'Price: Low to High'],
                ['value' => 'desc',    'name' => 'Price: High to Low'],
                ['value' => 'nearest', 'name' => 'Nearest First'],
            ],
        ];
    }

    // ══════════════════════════════════════════════════════
    //  SEARCH PROPERTIES
    //  Used by SearchController and FilterController.
    //  Pass $userId to scope to user's approved requests.
    // ══════════════════════════════════════════════════════

    public function searchProperties(array $params, ?int $userId = null): Collection
    {
        $query = $this->buildBaseQuery($params);
        $query = $this->applySort($query, $params);

        // Request-page scope
        if ($userId) {
            $query->whereHas('bookingAccepteds', fn($q) =>
            $q->whereHas('bookingRequest', fn($sq) =>
            $sq->where('user_id', $userId)->where('status', 'Approved')
            )
            );
        }

        $eagerLoads = $userId
            ? ['images', 'facilities', 'place.city', 'rooms', 'rooms.images', 'bookingAccepteds']
            : ['images', 'facilities', 'place.city', 'rooms'];

        return $query
            ->with($eagerLoads)
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->map(fn($p) => $this->appendComputedFields($p, $params));
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — BASE QUERY BUILDER
    // ══════════════════════════════════════════════════════

    private function buildBaseQuery(array $params)
    {
        $query = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->whereHas('rooms');

        $lat    = isset($params['lat'])    && $params['lat']    !== '' ? (float) $params['lat']    : null;
        $long   = isset($params['long'])   && $params['long']   !== '' ? (float) $params['long']   : null;
        $radius = isset($params['radius']) && $params['radius'] !== '' ? (float) $params['radius'] : self::DEFAULT_RADIUS_KM;

        // ── 1. Geo ────────────────────────────────────────
        if ($lat && $long) {
            // Haversine — properties within $radius km of coordinates
            // Backtick on `long` required — reserved MySQL keyword
            $haversine = "
                6371 * acos(
                    cos(radians(?)) * cos(radians(lat))
                    * cos(radians(`long`) - radians(?))
                    + sin(radians(?)) * sin(radians(lat))
                )
            ";

            $query
                ->selectRaw("properties.*, ({$haversine}) AS distance_km", [$lat, $long, $lat])
                ->whereRaw("({$haversine}) <= ?", [$lat, $long, $lat, $radius]);

        } else {
            // No coordinates — delegate to LocationService
            $placeIds = $this->locationService->resolvePlaceIds($params);

            if (!empty($placeIds)) {
                $query->whereIn('place_id', $placeIds);
            } elseif (!empty($params['location'])) {
                // Last resort — serialized address LIKE
                $query->whereRaw(
                    'LOWER(address) LIKE ?',
                    ['%' . strtolower(trim($params['location'])) . '%']
                );
            }
        }

        // ── 2. Availability ───────────────────────────────
        if (!empty($params['check_in']) && !empty($params['check_out'])) {
            $ci = $params['check_in'];
            $co = $params['check_out'];
            $query->whereHas('rooms', fn($q) =>
            $q->whereDoesntHave('bookings', fn($b) =>
            $b->where('checkin', '<', $co)->where('checkout', '>', $ci)
            )
            );
        }

        // ── 3. Guest capacity ─────────────────────────────
        if (!empty($params['adult']) && (int) $params['adult'] > 0) {
            $query->whereHas('rooms', fn($q) =>
            $q->where('guest_capacity', '>=', (int) $params['adult'])
            );
        }

        // ── 4. Facilities — AND logic ─────────────────────
        // Property::facilities() = BelongsToMany via property_facilities
        if (!empty($params['facilities'])) {
            foreach ((array) $params['facilities'] as $fid) {
                $query->whereHas('facilities', fn($q) =>
                $q->where('facility_subs.id', (int) $fid)
                );
            }
        }

        // ── 5. Hotel class ────────────────────────────────
        if (!empty($params['hotelClass'])) {
            $query->whereIn('property_class', (array) $params['hotelClass']);
        }

        // ── 6. Rating — floor match: "4" = 4.0–4.99 ──────
        if (!empty($params['rating'])) {
            $ratings = array_map('intval', (array) $params['rating']);
            $query->where(function ($q) use ($ratings) {
                foreach ($ratings as $r) {
                    $q->orWhereBetween('rating', [$r, $r + 0.99]);
                }
            });
        }

        // ── 7. Property type ──────────────────────────────
        if (!empty($params['propertyType'])) {
            $query->whereIn('property_category_id', (array) $params['propertyType']);
        }

        return $query;
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — SORT
    // ══════════════════════════════════════════════════════

    private function applySort($query, array $params)
    {
        $sortBy = $params['sortByPrice'] ?? null;
        $hasGeo = !empty($params['lat']) && !empty($params['long'])
            && $params['lat'] !== '' && $params['long'] !== '';

        $minPrice = fn(string $dir) => Room::select('base_price')
            ->whereColumn('rooms.property_id', 'properties.id')
            ->orderBy('base_price', $dir)
            ->limit(1);

        return match ($sortBy) {
            'asc'     => $query->orderBy($minPrice('asc'),  'asc'),
            'desc'    => $query->orderBy($minPrice('desc'), 'desc'),
            default   => $hasGeo
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderByDesc('rating'),
        };
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — COMPUTED FIELDS
    // ══════════════════════════════════════════════════════

    private function appendComputedFields(Property $property, array $params): Property
    {
        $property->min_price = $property->rooms->min('base_price');

        $hasGeo = !empty($params['lat']) && !empty($params['long'])
            && $params['lat'] !== '' && $params['long'] !== '';

        if ($hasGeo) {
            $property->distance_km = round((float) ($property->distance_km ?? 0), 1);
        }

        return $property;
    }
}
