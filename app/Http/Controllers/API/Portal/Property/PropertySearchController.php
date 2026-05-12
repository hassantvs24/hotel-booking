<?php

namespace App\Http\Controllers\API\Portal\Property;

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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PropertySearchController
 *
 * Single controller for all search, filter, and geo concerns.
 *
 * Routes:
 *   GET  /api/search/suggestions    → suggestions()
 *   POST /api/search/resolve-place  → resolvePlace()
 *   GET  /api/search                → search()          ← initial load + filter submit
 *   GET  /api/search/request        → searchRequests()  ← /request page
 *   GET  /api/search/filters        → getFilters()      ← dropdown options
 */
class PropertySearchController extends BaseController
{
    const DEFAULT_RADIUS_KM = 20;

    // ══════════════════════════════════════════════════════
    //  1. SUGGESTIONS
    //  GET /api/search/suggestions?q=Cox
    // ══════════════════════════════════════════════════════

    public function suggestions(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return $this->sendSuccess([]);
        }

        // ── Local DB first ─────────────────────────────────
        $places = Place::with('city.state.country')
            ->where('name', 'like', "%{$q}%")
            ->limit(4)->get()
            ->map(fn($p) => $this->formatPlace($p));

        $cities = City::with('state.country')
            ->where('name', 'like', "%{$q}%")
            ->limit(3)->get()
            ->map(fn($c) => [
                'type'        => 'city',
                'id'          => $c->id,
                'external_id' => null,
                'name'        => $c->name,
                'city'        => null,
                'state'       => $c->state?->name,
                'country'     => $c->state?->country?->name,
                'lat'         => $c->lat  ?? null,
                'long'        => $c->long ?? null,
                'source'      => 'local',
            ]);

        $states = State::with('country')
            ->where('name', 'like', "%{$q}%")
            ->limit(2)->get()
            ->map(fn($s) => [
                'type'        => 'state',
                'id'          => $s->id,
                'external_id' => null,
                'name'        => $s->name,
                'city'        => null,
                'state'       => null,
                'country'     => $s->country?->name,
                'lat'         => null,
                'long'        => null,
                'source'      => 'local',
            ]);

        $results = array_merge(
            $places->toArray(),
            $cities->toArray(),
            $states->toArray()
        );

        // ── Enrich with Nominatim when local results are sparse ──
        if (count($results) < 3) {
            $nominatim   = $this->nominatimSuggestions($q);
            $existingIds = collect($results)->pluck('external_id')->filter()->toArray();

            foreach ($nominatim as $nr) {
                if (!in_array($nr['external_id'], $existingIds)) {
                    $results[] = $nr;
                }
            }
        }

        return $this->sendSuccess(array_slice($results, 0, 8));
    }

    // ══════════════════════════════════════════════════════
    //  2. RESOLVE PLACE
    //  POST /api/search/resolve-place
    //  Called when user picks a Nominatim result (no local id yet).
    //  Stores place in DB and returns local place_id for future searches.
    // ══════════════════════════════════════════════════════

    public function resolvePlace(Request $request): JsonResponse
    {
        $request->validate([
            'external_id'  => 'required|string',
            'name'         => 'required|string',
            'lat'          => 'required|numeric',
            'long'         => 'required|numeric',
            'city'         => 'nullable|string',
            'state'        => 'nullable|string',
            'country'      => 'nullable|string',
            'full_address' => 'nullable|string',
        ]);

        // Already stored — just return it
        $existing = Place::where('external_id', $request->external_id)->first();
        if ($existing) {
            return $this->sendSuccess($this->formatPlace($existing));
        }

        $city = $request->city
            ? City::where('name', 'like', "%{$request->city}%")->first()
            : null;

        $place = Place::create([
            'city_id'         => $city?->id,
            'name'            => $request->name,
            'lat'             => $request->lat,
            'long'            => $request->long,
            'external_id'     => $request->external_id,
            'external_source' => 'nominatim',
            'full_address'    => $request->full_address ?? $request->name,
        ]);

        Log::info("Place stored from Nominatim: {$place->name} (#{$place->id})");

        return $this->sendSuccess($this->formatPlace($place));
    }

    // ══════════════════════════════════════════════════════
    //  3. FILTER OPTIONS
    //  GET /api/search/filters
    // ══════════════════════════════════════════════════════

    public function getFilters(): JsonResponse
    {
        return $this->sendSuccess([
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
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  4. MAIN SEARCH
    //  GET /api/search
    //  Handles both initial page load AND filter form submit.
    //  Single endpoint = always consistent results.
    // ══════════════════════════════════════════════════════

    public function search(Request $request): JsonResponse
    {
        $params = $request->all();

        $query = $this->buildQuery($params);
        $query = $this->applySort($query, $params);

        $properties = $query
            ->with(['images', 'facilities', 'place.city', 'rooms'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->map(fn($p) => $this->computed($p, $params));

        return $this->sendSuccess(['properties' => $properties]);
    }

    // ══════════════════════════════════════════════════════
    //  5. REQUEST PAGE SEARCH
    //  GET /api/search/request
    //  Same filters, scoped to approved booking requests for the user.
    // ══════════════════════════════════════════════════════

    public function searchRequests(Request $request): JsonResponse
    {
        $params = $request->all();
        $userId = $request->user()->id;

        $query = $this->buildQuery($params);
        $query = $this->applySort($query, $params);

        $query->whereHas('bookingAccepteds', fn($q) =>
        $q->whereHas('bookingRequest', fn($sq) =>
        $sq->where('user_id', $userId)->where('status', 'Approved')
        )
        );

        $properties = $query
            ->with(['images', 'facilities', 'place.city', 'rooms', 'rooms.images', 'bookingAccepteds'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->map(fn($p) => $this->computed($p, $params));

        return $this->sendSuccess([
            'properties'               => $properties,
            'accepted_properties_count' => $properties->count(),
        ]);
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — QUERY BUILDER
    // ══════════════════════════════════════════════════════

    private function buildQuery(array $params)
    {
        $query = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->whereHas('rooms');

        // ── Geo resolution priority ────────────────────────
        // 1. lat + long → Haversine radius (most accurate)
        // 2. place_id   → direct DB lookup (fast)
        // 3. location   → local text search → Nominatim fallback
        // 4. address    → serialized LIKE (last resort)

        $lat    = isset($params['lat'])    ? (float) $params['lat']    : null;
        $long   = isset($params['long'])   ? (float) $params['long']   : null;
        $radius = isset($params['radius']) ? (float) $params['radius'] : self::DEFAULT_RADIUS_KM;

        if ($lat && $long) {
            $haversine = "6371 * acos(
                cos(radians(?)) * cos(radians(lat))
                * cos(radians(`long`) - radians(?))
                + sin(radians(?)) * sin(radians(lat))
            )";

            $query
                ->selectRaw("properties.*, ({$haversine}) AS distance_km", [$lat, $long, $lat])
                ->whereRaw("({$haversine}) <= ?", [$lat, $long, $lat, $radius]);

        } else {
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

        // ── Availability ───────────────────────────────────
        if (!empty($params['check_in']) && !empty($params['check_out'])) {
            $ci = $params['check_in'];
            $co = $params['check_out'];
            $query->whereHas('rooms', fn($q) =>
            $q->whereDoesntHave('bookings', fn($b) =>
            $b->where('checkin', '<', $co)->where('checkout', '>', $ci)
            )
            );
        }

        // ── Guest capacity ─────────────────────────────────
        if (!empty($params['adult'])) {
            $query->whereHas('rooms', fn($q) =>
            $q->where('guest_capacity', '>=', (int) $params['adult'])
            );
        }

        // ── Facilities — AND logic (must have ALL selected) ──
        if (!empty($params['facilities'])) {
            foreach ((array) $params['facilities'] as $fid) {
                $query->whereHas('facilities', fn($q) =>
                $q->where('facility_subs.id', (int) $fid)
                );
            }
        }

        // ── Hotel class ────────────────────────────────────
        if (!empty($params['hotelClass'])) {
            $query->whereIn('property_class', (array) $params['hotelClass']);
        }

        // ── Rating — floor match (4 = 4.0–4.99) ───────────
        if (!empty($params['rating'])) {
            $ratings = array_map('intval', (array) $params['rating']);
            $query->where(function ($q) use ($ratings) {
                foreach ($ratings as $r) {
                    $q->orWhereBetween('rating', [$r, $r + 0.99]);
                }
            });
        }

        // ── Property type ──────────────────────────────────
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
        $sort   = $params['sortByPrice'] ?? 'rating';
        $hasGeo = !empty($params['lat']) && !empty($params['long']);

        $minPrice = fn($dir) => Room::select('base_price')
            ->whereColumn('rooms.property_id', 'properties.id')
            ->orderBy('base_price', $dir)
            ->limit(1);

        return match ($sort) {
            'nearest' => $hasGeo
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderByDesc('rating'),
            'asc'     => $query->orderBy($minPrice('asc'),  'asc'),
            'desc'    => $query->orderBy($minPrice('desc'), 'desc'),
            default   => $query->orderByDesc('rating'),
        };
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — COMPUTED FIELDS
    // ══════════════════════════════════════════════════════

    private function computed(Property $property, array $params): Property
    {
        $property->min_price = $property->rooms->min('base_price');

        if (!empty($params['lat']) && !empty($params['long'])) {
            $property->distance_km = round((float) ($property->distance_km ?? 0), 1);
        }

        return $property;
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — GEO: RESOLVE PLACE IDs
    // ══════════════════════════════════════════════════════

    private function resolvePlaceIds(array $params): array
    {
        // Fast path — place already selected from dropdown
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
        $ids      = [];

        // Places
        $ids = array_merge($ids,
            Place::where('name', 'like', "%{$location}%")->pluck('id')->toArray()
        );

        // Cities → places
        $cityIds = City::where('name', 'like', "%{$location}%")->pluck('id');
        if ($cityIds->isNotEmpty()) {
            $ids = array_merge($ids,
                Place::whereIn('city_id', $cityIds)->pluck('id')->toArray()
            );
        }

        // States → cities → places
        $stateIds = State::where('name', 'like', "%{$location}%")->pluck('id');
        if ($stateIds->isNotEmpty()) {
            $stateCityIds = City::whereIn('state_id', $stateIds)->pluck('id');
            $ids = array_merge($ids,
                Place::whereIn('city_id', $stateCityIds)->pluck('id')->toArray()
            );
        }

        $ids = array_unique(array_values($ids));

        // Nothing in local DB — try Nominatim, store result
        if (empty($ids)) {
            $stored = $this->nominatimResolve($location);
            if ($stored) {
                Log::info("Nominatim resolved '{$location}' → place #{$stored->id}");
                $ids = [$stored->id];
            }
        }

        return $ids;
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE — NOMINATIM
    // ══════════════════════════════════════════════════════

    private function nominatimHttp(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'User-Agent'      => config('app.name') . '/1.0 (contact@oystay.com)',
            'Accept-Language' => 'en',
        ])->timeout(8);
    }

    private function nominatimResolve(string $query): ?Place
    {
        try {
            $results = $this->nominatimHttp()->get('https://nominatim.openstreetmap.org/search', [
                'q'               => $query,
                'format'          => 'json',
                'limit'           => 1,
                'addressdetails'  => 1,
                'accept-language' => 'en',
            ])->json();

            if (empty($results)) return null;

            return $this->nominatimStore($results[0]);

        } catch (\Exception $e) {
            Log::warning('Nominatim resolve failed: ' . $e->getMessage());
            return null;
        }
    }

    private function nominatimSuggestions(string $query): array
    {
        try {
            $results = $this->nominatimHttp()->timeout(5)->get('https://nominatim.openstreetmap.org/search', [
                'q'               => $query,
                'format'          => 'json',
                'limit'           => 5,
                'addressdetails'  => 1,
                'accept-language' => 'en',
            ])->json();

            return collect($results)->map(fn($r) => [
                'type'        => 'place',
                'id'          => null,
                'external_id' => (string) $r['place_id'],
                'name'        => $r['display_name'],
                'city'        => $r['address']['city']  ?? $r['address']['town']  ?? null,
                'state'       => $r['address']['state']   ?? null,
                'country'     => $r['address']['country'] ?? null,
                'lat'         => (float) $r['lat'],
                'long'        => (float) $r['lon'],
                'full_address'=> $r['display_name'],
                'source'      => 'nominatim',
            ])->toArray();

        } catch (\Exception $e) {
            Log::warning('Nominatim suggestions failed: ' . $e->getMessage());
            return [];
        }
    }

    private function nominatimStore(array $r): Place
    {
        $cityName = $r['address']['city']
            ?? $r['address']['town']
            ?? $r['address']['village']
            ?? $r['address']['suburb']
            ?? null;

        $city = $cityName
            ? City::where('name', 'like', "%{$cityName}%")->first()
            : null;

        return Place::firstOrCreate(
            ['external_id' => (string) $r['place_id']],
            [
                'city_id'         => $city?->id,
                'name'            => $r['display_name'],
                'lat'             => $r['lat'],
                'long'            => $r['lon'],
                'external_source' => 'nominatim',
                'full_address'    => $r['display_name'],
            ]
        );
    }

    private function formatPlace(Place $p): array
    {
        return [
            'type'        => 'place',
            'id'          => $p->id,
            'external_id' => $p->external_id,
            'name'        => $p->name,
            'city'        => $p->city?->name,
            'state'       => $p->city?->state?->name,
            'country'     => $p->city?->state?->country?->name,
            'lat'         => $p->lat,
            'long'        => $p->long,
            'source'      => $p->external_source ?? 'local',
        ];
    }
}
