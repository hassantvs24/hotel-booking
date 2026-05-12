<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\City;
use App\Models\Place;
use App\Models\Property;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchController extends BaseController
{
    // ══════════════════════════════════════════════════════
    //  SUGGESTIONS ENDPOINT
    //  GET /api/search/suggestions?q=Cox
    //
    //  Flow:
    //  1. Search local places, cities, states
    //  2. If nothing found → call Nominatim
    //  3. Return merged results (both local + Nominatim)
    // ══════════════════════════════════════════════════════

    // TODO: Implemented

    public function suggestions(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return $this->sendSuccess([]);
        }

        $results = [];

        // ── 1. Local DB search ─────────────────────────────
        $places = Place::with('city.state.country')
            ->where('name', 'like', "%{$q}%")
            ->limit(4)
            ->get()
            ->map(fn($p) => $this->formatPlace($p));

        $cities = City::with('state.country')
            ->where('name', 'like', "%{$q}%")
            ->limit(3)
            ->get()
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
            ->limit(2)
            ->get()
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

        // ── 2. Always try Nominatim in parallel to enrich results
        //       even if we have some local results ──────────────────
        if (count($results) < 3) {
            $nominatimResults = $this->fetchNominatimSuggestions($q);

            // Merge, avoiding duplicates by external_id
            $existingExternalIds = collect($results)
                ->pluck('external_id')
                ->filter()
                ->toArray();

            foreach ($nominatimResults as $nr) {
                if (!in_array($nr['external_id'], $existingExternalIds)) {
                    $results[] = $nr;
                }
            }
        }

        return $this->sendSuccess(array_slice($results, 0, 8));
    }

    // ══════════════════════════════════════════════════════
    //  RESOLVE PLACE ENDPOINT
    //  POST /api/search/resolve-place
    //
    //  Called by Vue when user selects a Nominatim result
    //  (id = null). Stores the place locally and returns
    //  the new local place_id so subsequent searches are fast.
    // ══════════════════════════════════════════════════════
// TODO: Implemented
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

        // Already stored from a previous search?
        $existing = Place::where('external_id', $request->external_id)->first();
        if ($existing) {
            return $this->sendSuccess($this->formatPlace($existing));
        }

        // Try to match a local city
        $city = null;
        if ($request->city) {
            $city = City::where('name', 'like', "%{$request->city}%")->first();
        }

        // Store the Nominatim place in local DB
        $place = Place::create([
            'city_id'         => $city?->id,
            'name'            => $request->name,
            'lat'             => $request->lat,
            'long'            => $request->long,
            'external_id'     => $request->external_id,
            'external_source' => 'nominatim',
            'full_address'    => $request->full_address ?? $request->name,
        ]);

        Log::info("New place stored from Nominatim: {$place->name} (external_id: {$place->external_id})");

        return $this->sendSuccess($this->formatPlace($place));
    }

    // ══════════════════════════════════════════════════════
    //  MAIN SEARCH ENDPOINT
    //  GET /api/search?location=Cox's Bazar&place_id=12&place_type=place&...
    //
    //  Flow:
    //  1. If place_id given (user picked suggestion) → fast geo query
    //  2. If location text only → search local DB
    //  3. If still nothing → Nominatim → store → use new place_id
    //  4. Final fallback → address LIKE (your original logic)
    // ══════════════════════════════════════════════════════

    public function search(Request $request): JsonResponse
    {
        $location  = trim($request->input('location', ''));
        $checkIn   = $request->input('check_in');
        $checkOut  = $request->input('check_out');
        $placeId   = $request->input('place_id');
        $placeType = $request->input('place_type', 'place');
        $adult     = (int) $request->input('adult', 1);

        // ── Step 1: Resolve place IDs ──────────────────────
        $placeIds = [];

        if ($placeId) {
            // Fast path — user selected from dropdown, place already in DB
            $placeIds = $this->resolvePlaceIdsBySelection((int) $placeId, $placeType);

        } elseif ($location) {
            // Search local DB
            $placeIds = $this->resolvePlaceIdsByText($location);

            if (empty($placeIds)) {
                // Local miss → Nominatim → store → get place_id
                $newPlace = $this->resolveOrCreateFromNominatim($location);
                if ($newPlace) {
                    $placeIds = [$newPlace->id];
                    Log::info("Search: Nominatim resolved '{$location}' → place #{$newPlace->id}");
                }
            }
        }

        // ── Step 2: Build property query ───────────────────
        $query = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->whereHas('rooms');

        if (!empty($placeIds)) {
            $query->whereIn('place_id', $placeIds);
        } elseif ($location) {
            // Ultimate fallback — original address LIKE
            $query->whereRaw(
                'LOWER(address) LIKE ?',
                ['%' . strtolower($location) . '%']
            );
        }

        // Availability: corrected overlap check
        if ($checkIn && $checkOut) {
            $query->whereHas('rooms', function ($q) use ($checkIn, $checkOut) {
                $q->whereDoesntHave('bookings', function ($b) use ($checkIn, $checkOut) {
                    $b->where('checkin',  '<', $checkOut)
                        ->where('checkout', '>', $checkIn);
                });
            });
        }

        // Guest capacity
        if ($adult > 0) {
            $query->whereHas('rooms', fn($q) =>
            $q->where('guest_capacity', '>=', $adult)
            );
        }

        $properties = $query
            ->with(['images', 'facilities', 'place.city.state', 'rooms'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->map(function ($property) {
                $property->min_price = $property->rooms->min('base_price');
                return $property;
            });

        return $this->sendSuccess($properties);
    }

    // ══════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════

    /**
     * User picked from dropdown — resolve all place IDs
     */
    private function resolvePlaceIdsBySelection(int $id, string $type): array
    {
        return match ($type) {
            'city'  => Place::where('city_id', $id)->pluck('id')->toArray(),
            'state' => Place::whereHas('city', fn($q) =>
            $q->whereHas('state', fn($s) => $s->where('id', $id))
            )->pluck('id')->toArray(),
            default => [$id], // 'place'
        };
    }

    /**
     * Free-text → search local DB across all geo tables
     */
    private function resolvePlaceIdsByText(string $location): array
    {
        $placeIds = Place::where('name', 'like', "%{$location}%")
            ->pluck('id')->toArray();

        $cityIds = City::where('name', 'like', "%{$location}%")->pluck('id');
        if ($cityIds->isNotEmpty()) {
            $more     = Place::whereIn('city_id', $cityIds)->pluck('id')->toArray();
            $placeIds = array_merge($placeIds, $more);
        }

        $stateIds = State::where('name', 'like', "%{$location}%")->pluck('id');
        if ($stateIds->isNotEmpty()) {
            $stateCityIds = City::whereIn('state_id', $stateIds)->pluck('id');
            $more         = Place::whereIn('city_id', $stateCityIds)->pluck('id')->toArray();
            $placeIds     = array_merge($placeIds, $more);
        }

        return array_unique(array_values($placeIds));
    }

    /**
     * Nominatim → store result → return Place model
     * Uses firstOrCreate so duplicate searches never create duplicate rows
     */
    private function resolveOrCreateFromNominatim(string $query): ?Place
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . '/1.0 (contact@oystay.com)',
            ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'              => $query,
                    'format'         => 'json',
                    'limit'          => 1,
                    'addressdetails' => 1,
                    'accept-language' => 'en'
                ])
                ->json();

            if (empty($response)) {
                return null;
            }

            return $this->storeNominatimResult($response[0]);

        } catch (\Exception $e) {
            Log::warning('Nominatim resolveOrCreate failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store a single Nominatim result in local DB
     */
    private function storeNominatimResult(array $r): Place
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

    /**
     * Nominatim suggestion results — shaped for dropdown
     */
    private function fetchNominatimSuggestions(string $query): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . '/1.0 (contact@oystay.com)',
            ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'              => $query,
                    'format'         => 'json',
                    'limit'          => 5,
                    'addressdetails' => 1,
                    'accept-language' => 'en'
                ])
                ->json();

            return collect($response)->map(fn($r) => [
                'type'        => 'place',
                'id'          => null,                  // not in local DB yet
                'external_id' => (string) $r['place_id'],
                'name'        => $r['display_name'],
                'city'        => $r['address']['city']    ?? $r['address']['town']    ?? null,
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

    /**
     * Format a Place model for API response
     */
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
