<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LocationService
 *
 * Handles ALL geo and place concerns:
 *   - Typeahead suggestions (local DB + Nominatim)
 *   - Place ID resolution: place / city / state / country / freetext
 *   - Nominatim search, store, and deduplication
 *   - Place formatting for API responses
 */
class LocationService
{
    // ══════════════════════════════════════════════════════
    //  SUGGESTIONS
    //  Local DB first → Nominatim fallback when sparse
    // ══════════════════════════════════════════════════════

    public function getSuggestions(string $q): array
    {
        if (strlen(trim($q)) < 2) return [];

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

        // ── Country match ──────────────────────────────────
        $countries = Country::where('name', 'like', "%{$q}%")
            ->limit(2)->get()
            ->map(fn($c) => [
                'type'        => 'country',
                'id'          => $c->id,
                'external_id' => null,
                'name'        => $c->name,
                'city'        => null,
                'state'       => null,
                'country'     => $c->name,
                'lat'         => null,
                'long'        => null,
                'source'      => 'local',
            ]);

        $results = array_merge(
            $places->toArray(),
            $cities->toArray(),
            $states->toArray(),
            $countries->toArray()
        );

        // Enrich with Nominatim when local results are sparse
        if (count($results) < 3) {
            $nominatim   = $this->nominatimSuggestions($q);
            $existingIds = collect($results)->pluck('external_id')->filter()->toArray();
            foreach ($nominatim as $nr) {
                if (!in_array($nr['external_id'], $existingIds)) {
                    $results[] = $nr;
                }
            }
        }

        return array_slice($results, 0, 8);
    }

    // ══════════════════════════════════════════════════════
    //  RESOLVE & STORE PLACE
    // ══════════════════════════════════════════════════════

    public function resolveAndStorePlace(array $data): Place
    {
        $existing = Place::where('external_id', $data['external_id'])->first();
        if ($existing) return $existing;

        $city = !empty($data['city'])
            ? City::where('name', 'like', "%{$data['city']}%")->first()
            : null;

        $place = Place::create([
            'city_id'         => $city?->id,
            'name'            => $data['name'],
            'lat'             => $data['lat'],
            'long'            => $data['long'],
            'external_id'     => $data['external_id'],
            'external_source' => 'nominatim',
            'full_address'    => $data['full_address'] ?? $data['name'],
        ]);

        Log::info("Place stored from Nominatim: {$place->name} (external_id: {$place->external_id})");

        return $place;
    }

    // ══════════════════════════════════════════════════════
    //  RESOLVE PLACE IDs FROM PARAMS
    //
    public function resolvePlaceIds(array $params): array
    {
        if (!empty($params['place_id'])) {
            $id   = (int) $params['place_id'];
            $type = $params['place_type'] ?? 'place';

            return match ($type) {

                'city' => Place::where('city_id', $id)
                    ->pluck('id')->toArray(),

                'state' => Place::whereHas('city', fn($q) =>
                $q->whereHas('state', fn($s) => $s->where('id', $id))
                )->pluck('id')->toArray(),

                'country' => Place::whereHas('city', fn($q) =>
                $q->whereHas('state', fn($s) =>
                $s->whereHas('country', fn($c) => $c->where('id', $id))
                )
                )->pluck('id')->toArray(),

                // Place picked directly
                default => [$id],
            };
        }

        if (empty($params['location'])) return [];

        $location = trim($params['location']);
        $ids      = [];

        $ids = array_merge($ids,
            Place::where('name', 'like', "%{$location}%")->pluck('id')->toArray()
        );

        $cityIds = City::where('name', 'like', "%{$location}%")->pluck('id');
        if ($cityIds->isNotEmpty()) {
            $ids = array_merge($ids,
                Place::whereIn('city_id', $cityIds)->pluck('id')->toArray()
            );
        }

        $stateIds = State::where('name', 'like', "%{$location}%")->pluck('id');
        if ($stateIds->isNotEmpty()) {
            $stateCityIds = City::whereIn('state_id', $stateIds)->pluck('id');
            if ($stateCityIds->isNotEmpty()) {
                $ids = array_merge($ids,
                    Place::whereIn('city_id', $stateCityIds)->pluck('id')->toArray()
                );
            }
        }

        // Country wise search resolved
        $countryIds = Country::where('name', 'like', "%{$location}%")->pluck('id');
        if ($countryIds->isNotEmpty()) {
            $countryStateIds = State::whereIn('country_id', $countryIds)->pluck('id');
            if ($countryStateIds->isNotEmpty()) {
                $countryCityIds = City::whereIn('state_id', $countryStateIds)->pluck('id');
                if ($countryCityIds->isNotEmpty()) {
                    $ids = array_merge($ids,
                        Place::whereIn('city_id', $countryCityIds)->pluck('id')->toArray()
                    );
                }
            }
        }

        $ids = array_unique(array_values($ids));

        // ── 5. Nothing in local DB — try Nominatim ─────────
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
    //  FORMAT PLACE
    // ══════════════════════════════════════════════════════

    public function formatPlace(Place $p): array
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

    // ══════════════════════════════════════════════════════
    //  PRIVATE — NOMINATIM
    // ══════════════════════════════════════════════════════

    private function nominatimClient(int $timeout = 8)
    {
        return Http::withHeaders([
            'User-Agent'      => config('app.name') . '/1.0 (contact@oystay.com)',
            'Accept-Language' => 'en',
        ])->timeout($timeout);
    }

    private function nominatimResolve(string $location): ?Place
    {
        try {
            $response = $this->nominatimClient(8)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'               => $location,
                    'format'          => 'json',
                    'limit'           => 1,
                    'addressdetails'  => 1,
                    'accept-language' => 'en',
                ])->json();

            if (empty($response)) return null;

            return $this->nominatimStore($response[0]);

        } catch (\Exception $e) {
            Log::warning('Nominatim resolve failed: ' . $e->getMessage());
            return null;
        }
    }

    private function nominatimSuggestions(string $q): array
    {
        try {
            $response = $this->nominatimClient(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'               => $q,
                    'format'          => 'json',
                    'limit'           => 5,
                    'addressdetails'  => 1,
                    'accept-language' => 'en',
                ])->json();

            return collect($response)->map(fn($r) => [
                'type'        => 'place',
                'id'          => null,
                'external_id' => (string) $r['place_id'],
                'name'        => $r['display_name'],
                'city'        => $r['address']['city'] ?? $r['address']['town'] ?? null,
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
}
