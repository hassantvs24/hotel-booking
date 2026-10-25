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

    // Distance in km between (?, ?) and a hotel: bindings are lat, long, lat. Backtick on `long` required — reserved MySQL keyword
    private const HAVERSINE = '6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(`long`) - radians(?)) + sin(radians(?)) * sin(radians(lat)))';

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
            // the official star class of the hotels a guest can find (5 stars first, "Unrated" last): a class nobody has is not offered
            'hotelClasses'  => Property::query()
                ->where('status', Property::STATUS_PUBLISHED)
                ->whereHas('rooms')
                ->distinct()
                ->orderBy('property_class')   // an enum sorts in its own order: 7 Stars ... 1 Star, Unrated
                ->pluck('property_class')
                ->all(),
            'ratings'       => [9, 8, 7, 6],   // "9 and above" ... (scores are out of 10, the same scale the cards show)
            'propertyTypes' => PropertyCategory::select('id', 'name')->get(),
            'sortOptions'   => [
                //['value' => 'rating',  'name' => 'Top Rated'],
                ['value' => 'asc',     'name' => 'Price: Low to High'],
                ['value' => 'desc',    'name' => 'Price: High to Low'],
                //['value' => 'nearest', 'name' => 'Nearest First'],
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
            ? ['images', 'facilities', 'place.city', 'propertyCategory:id,name', 'rooms', 'rooms.images', 'bookingAccepteds']
            : ['images', 'facilities', 'place.city', 'propertyCategory:id,name', 'rooms'];

        $sortBy = $params['sortByPrice'] ?? null;

        $results = $query
            ->with($eagerLoads)
            ->get()
            ->map(fn($p) => $this->appendComputedFields($p, $params));

        // Price sort — applied on collection after fetch
        // because lowest_room_price is a computed accessor not a DB column
        if ($sortBy === 'asc') {
            return $results->sortBy(fn($p) => $p->min_price_with_tax ?? $p->min_price ?? 0)->values();
        }

        if ($sortBy === 'desc') {
            return $results->sortByDesc(fn($p) => $p->min_price_with_tax ?? $p->min_price ?? 0)->values();
        }

        return $results;
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
            $query
                ->selectRaw('properties.*, (' . self::HAVERSINE . ') AS distance_km', [$lat, $long, $lat])
                ->whereRaw('(' . self::HAVERSINE . ') <= ?', [$lat, $long, $lat, $radius]);

        } else {
            // No coordinates — delegate to LocationService
            $placeIds = $this->locationService->resolvePlaceIds($params);

            if (!empty($placeIds)) {
                $query->whereIn('place_id', $placeIds);
            } elseif (!empty($params['location'])) {
                $text = '%' . strtolower(trim($params['location'])) . '%';

                $inAddress = Property::query()
                    ->where('status', Property::STATUS_PUBLISHED)
                    ->whereRaw('LOWER(address) LIKE ?', [$text])
                    ->exists();

                // The words are in no hotel's address: ask the map where they are and take the hotels around that spot
                $spot = $inAddress ? null : $this->locationService->geocode(trim($params['location']));

                if ($spot) {
                    $query->whereRaw('(' . self::HAVERSINE . ') <= ?', [$spot['lat'], $spot['long'], $spot['lat'], $radius]);
                } else {
                    $query->whereRaw('LOWER(address) LIKE ?', [$text]);
                }
            }
        }

        // ── 2. + 3. Availability, capacity and the number of rooms ─────────────
        // One room can do all of it at once: free on those dates (the same rule the property page uses: only reserved /
        // approved bookings block a room, a cancelled or refunded one does not), in use (not under maintenance), and big
        // enough for its share of the party. The property needs as many such rooms as the guest asked for.
        $checkIn  = !empty($params['check_in'])  ? $params['check_in']  : null;
        $checkOut = !empty($params['check_out']) ? $params['check_out'] : null;
        $adults   = max(0, (int) ($params['adult'] ?? 0));
        $wanted   = max(1, (int) ($params['room'] ?? 1));

        if ($checkIn && $checkOut || $adults > 0 || $wanted > 1) {
            $share = $adults > 0 ? (int) ceil($adults / $wanted) : 0;

            $query->whereHas('rooms', function ($q) use ($checkIn, $checkOut, $share) {
                $q->where(fn ($s) => $s->whereNull('status')->orWhereNotIn('status', ['Maintenance', 'Disabled', 'Inactive']));

                if ($checkIn && $checkOut) {
                    $q->whereDoesntHave('bookings', fn ($b) => $b->checkDateOverlap([], $checkIn, $checkOut));
                }
                if ($share > 0) {
                    $q->where('guest_capacity', '>=', $share);
                }
            }, '>=', $wanted);
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

        // ── 6. Rating — "7" means a score of 7 and above (the scores are out of 10) ──────
        if (!empty($params['rating'])) {
            $least = min(array_map('floatval', (array) $params['rating']));
            // the guests' score once there are reviews (the same score the list is sorted by), otherwise the starting score
            $query->whereRaw('IF(reviews_count > 0, reviews_avg_rating, rating) >= ?', [$least]);
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

        // Price sort (asc/desc) is handled AFTER fetch on the collection
        // because lowest_room_price is a computed accessor not a real DB column.
        // Returning the query unmodified here — collection sort in searchProperties().
        if (in_array($sortBy, ['asc', 'desc'])) {
            return $query;
        }

        return match ($sortBy) {
            default   => $hasGeo
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderByRaw('IF(reviews_count > 0, reviews_avg_rating, rating) DESC'),   // guests' score once there are reviews
        };
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — COMPUTED FIELDS
    // ══════════════════════════════════════════════════════

    private function appendComputedFields(Property $property, array $params): Property
    {
        $property->min_price = $property->rooms->min('base_price');
        // What guests actually pay per night for the cheapest room: VAT and service charge included
        $property->min_price_with_tax = app(\App\Services\Pricing\PriceService::class)->lowestWithTax($property->rooms, $property) ?? 0;

        $hasGeo = !empty($params['lat']) && !empty($params['long'])
            && $params['lat'] !== '' && $params['long'] !== '';

        if ($hasGeo) {
            $property->distance_km = round((float) ($property->distance_km ?? 0), 1);
        }

        return $property;
    }
}
