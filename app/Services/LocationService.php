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

    /**
     * @param bool $createHierarchy true only for signed-in owners (property form); the open search
     *                              endpoint just matches what already exists and never adds countries/states/cities.
     */
    public function resolveAndStorePlace(array $data, bool $createHierarchy = false): Place
    {
        $chain = $this->ensureHierarchy($data, $createHierarchy);

        $existing = Place::where('external_id', $data['external_id'])->first();
        if ($existing) {
            // A place saved earlier without its district gets it now
            if (!$existing->city_id && $chain['city']) {
                $existing->update(['city_id' => $chain['city']->id]);
            }

            return $existing;
        }

        $city = $chain['city'];

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
            'city_id'     => $p->city_id,
            'state_id'    => $p->city?->state_id,
            'country_id'  => $p->city?->state?->country_id,
            'lat'         => $p->lat,
            'long'        => $p->long,
            'source'      => $p->external_source ?? 'local',
        ];
    }

    // ══════════════════════════════════════════════════════
    //  COUNTRY → DIVISION → DISTRICT → PLACE
    // ══════════════════════════════════════════════════════

    /**
     * What a form needs after the owner picks a map result: the place saved, and the country / state
     * (division) / city (district) it belongs to, all created on the way when they are new.
     *
     * A result that is itself a whole division or district gives no place, only the chain above it.
     */
    public function resolveLocation(array $data): array
    {
        $chain = $this->ensureHierarchy($data, true);

        $isRegion = in_array($data['kind'] ?? null, ['state', 'state_district', 'county', 'country'], true);
        $place    = $isRegion ? null : $this->resolveAndStorePlace($data, true)->loadMissing('city.state.country');

        $city    = $place?->city ?? $chain['city'];
        $state   = $city?->state ?? $chain['state'];
        $country = $state?->country ?? $chain['country'];

        return [
            'place'   => $place ? ['id' => $place->id, 'name' => $place->name, 'city_id' => $place->city_id] : null,
            'country' => $country ? ['id' => $country->id, 'name' => $country->name] : null,
            'state'   => $state ? ['id' => $state->id, 'name' => $state->name, 'country_id' => $state->country_id] : null,
            'city'    => $city ? ['id' => $city->id, 'name' => $city->name, 'state_id' => $city->state_id] : null,
        ];
    }

    /**
     * Find, or create, the country, division and district named in a map result.
     * Matching ignores words like "Division" / "District" and the Chattogram / Chittagong spelling, so a
     * hotel in "Chattogram Division" lands in our existing "Chittagong Division" rather than a duplicate.
     *
     * @return array{country: ?Country, state: ?State, city: ?City}
     */
    public function ensureHierarchy(array $data, bool $create = true): array
    {
        $none = ['country' => null, 'state' => null, 'city' => null];

        if (blank($data['country'] ?? null)) {
            return $none;
        }

        $country = $this->matchByName(Country::query(), $data['country'])
            ?? ($create ? Country::create(['name' => trim($data['country'])]) : null);

        if (!$country) {
            return $none;
        }

        $state = null;
        if (filled($data['state'] ?? null)) {
            $state = $this->matchByName(State::where('country_id', $country->id), $data['state'])
                ?? ($create ? State::create(['name' => trim($data['state']), 'country_id' => $country->id]) : null);
        }

        $city = null;
        // The districts of Bangladesh come as "state_district"; elsewhere the town/city is the best we have
        $cityName = $data['district'] ?? $data['city'] ?? null;

        if ($state && filled($cityName)) {
            $city = $this->matchByName(City::where('state_id', $state->id), $cityName)
                ?? ($create ? City::create([
                    'name'     => $this->cleanName($cityName, ['district', 'zila']),
                    'state_id' => $state->id,
                    'lat'      => $data['lat'] ?? null,
                    'long'     => $data['long'] ?? null,
                ]) : null);
        }

        return ['country' => $country, 'state' => $state, 'city' => $city];
    }

    /** The first row whose name matches once "Division" / "District" etc. and spelling variants are ignored */
    private function matchByName($query, string $name)
    {
        $wanted = $this->normalizeName($name);

        return $query->get()->first(fn ($row) => $this->normalizeName($row->name) === $wanted);
    }

    private function normalizeName(string $name): string
    {
        $n = mb_strtolower(trim($name));
        $n = preg_replace('/\b(division|district|zila|city|metropolitan)\b/u', '', $n);
        $n = trim(preg_replace('/\s+/u', ' ', $n));

        // Spellings the map and our own data disagree on
        return strtr($n, [
            'chattogram' => 'chittagong', 'barisal' => 'barishal', 'comilla' => 'cumilla',
            'bogra' => 'bogura', 'jessore' => 'jashore', 'dacca' => 'dhaka',
        ]);
    }

    private function cleanName(string $name, array $words): string
    {
        return trim(preg_replace('/\s+/u', ' ', preg_replace('/\b(' . implode('|', $words) . ')\b/iu', '', $name)));
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
                'district'    => $r['address']['state_district'] ?? null,   // Bangladesh: the zila, e.g. "Moulvibazar District"
                'state'       => $r['address']['state']   ?? null,
                'country'     => $r['address']['country'] ?? null,
                'kind'        => $r['addresstype'] ?? null,                 // city, suburb, town, state, state_district ...
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
