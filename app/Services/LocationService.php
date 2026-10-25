<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\Property;
use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LocationService
 *
 * Handles ALL geo and place concerns:
 *   - Typeahead suggestions (our own areas that have hotels, map results when we have few)
 *   - Place ID resolution: place / city / state / country / typed words
 *   - Nominatim search, store, and deduplication
 *   - Place formatting for API responses
 */
class LocationService
{
    /** The countries, divisions, districts and places by name, built once per request (about 5,500 rows) */
    private ?array $index = null;

    // ══════════════════════════════════════════════════════
    //  SUGGESTIONS
    //  Only areas where a guest can really book a hotel are offered, each with how many hotels it has,
    //  so nobody picks an area and then finds "no property". Spelling does not matter ("Chattogram" finds
    //  "Chittagong", "Cox Bazar" finds "Cox's Bazar"). The map service is asked only when we have fewer than 3 answers.
    // ══════════════════════════════════════════════════════

    /**
     * @param bool $forListing true for an owner filling the property form: there it is the PLACES an area holds that
     *                         matter (the first hotel of a district has nobody before it), not the hotels
     */
    public function getSuggestions(string $q, bool $forListing = false): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) return [];

        $needle = $this->normalizeName($q);
        if ($needle === '') return [];

        $index   = $this->areaIndex();
        $counts  = $this->hotelCounts();
        $results = [];
        $seen    = [];

        // [which part of the index, how many to offer]
        foreach (['place' => ['places', 4], 'city' => ['cities', 3], 'state' => ['states', 2], 'country' => ['countries', 2]] as $type => [$bucket, $limit]) {
            $found = [];
            foreach ($index[$bucket] as $id => $row) {
                if (str_contains($row['norm'], $needle)) {
                    $found[$id] = $row['norm'] === $needle ? 0 : (str_starts_with($row['norm'], $needle) ? 1 : 2);   // exact, "starts with", "contains"
                }
            }
            asort($found);

            $offered = 0;
            foreach (array_keys($found) as $id) {
                if ($offered >= $limit) break;

                $placeIds = $this->placeIdsUnder($type, $id, $index);
                $hotels   = $this->sumHotels($placeIds, $counts);
                $size     = $forListing ? count($placeIds) : $hotels;
                if ($size === 0) continue;   // nothing to book there: not offered

                // a district, a division and a "City" row that mean the same area (the same name, the same places with hotels) are one suggestion
                $sorted = $forListing ? $placeIds : array_values(array_filter($placeIds, fn ($pid) => isset($counts[$pid])));
                sort($sorted);
                $key    = $type === 'place' ? "place|{$id}" : $index[$bucket][$id]['norm'] . '|' . md5(implode(',', $sorted));
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $results[] = $this->suggestionFor($type, $id, $index) + ($forListing ? [] : ['hotels' => $hotels]);
                $offered++;
            }
        }

        // Enrich with the map service when our own answers are few
        if (count($results) < 3) {
            $existingIds = collect($results)->pluck('external_id')->filter()->toArray();
            foreach ($this->nominatimSuggestions($q) as $nr) {
                if (!in_array($nr['external_id'], $existingIds)) {
                    $results[] = $nr;
                }
            }
        }

        return array_slice($results, 0, 8);
    }

    private function suggestionFor(string $type, int $id, array $index): array
    {
        if ($type === 'place') {
            $p = $index['places'][$id];
            $c = $index['cities'][$p['city_id']] ?? null;
            $s = $c ? ($index['states'][$c['state_id']] ?? null) : null;
            $k = $s ? ($index['countries'][$s['country_id']] ?? null) : null;

            return [
                'type' => 'place', 'id' => $id, 'external_id' => $p['external_id'], 'name' => $p['name'],
                'city' => $c['name'] ?? null, 'state' => $s['name'] ?? null, 'country' => $k['name'] ?? null,
                'city_id' => $p['city_id'], 'state_id' => $c['state_id'] ?? null, 'country_id' => $s['country_id'] ?? null,
                'lat' => $p['lat'], 'long' => $p['long'], 'source' => $p['source'],
            ];
        }

        if ($type === 'city') {
            $c = $index['cities'][$id];
            $s = $index['states'][$c['state_id']] ?? null;
            $k = $s ? ($index['countries'][$s['country_id']] ?? null) : null;

            return ['type' => 'city', 'id' => $id, 'external_id' => null, 'name' => $c['name'], 'city' => null,
                'state' => $s['name'] ?? null, 'country' => $k['name'] ?? null, 'lat' => $c['lat'], 'long' => $c['long'], 'source' => 'local'];
        }

        if ($type === 'state') {
            $s = $index['states'][$id];
            $k = $index['countries'][$s['country_id']] ?? null;

            return ['type' => 'state', 'id' => $id, 'external_id' => null, 'name' => $s['name'], 'city' => null,
                'state' => null, 'country' => $k['name'] ?? null, 'lat' => null, 'long' => null, 'source' => 'local'];
        }

        $k = $index['countries'][$id];

        return ['type' => 'country', 'id' => $id, 'external_id' => null, 'name' => $k['name'], 'city' => null,
            'state' => null, 'country' => $k['name'], 'lat' => null, 'long' => null, 'source' => 'local'];
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
                $this->index = null;
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
        $this->index = null;

        Log::info("Place stored from Nominatim: {$place->name} (external_id: {$place->external_id})");

        return $place;
    }

    // ══════════════════════════════════════════════════════
    //  RESOLVE PLACE IDs FROM PARAMS
    //
    //  Whatever the guest picked or typed, the answer is "the places under that area":
    //   - an area picked from the list (a place, a district, a division, a country)
    //   - if that area has no hotel (a map result saved earlier, an empty district), the NAME the guest sees is looked up instead
    //   - typed words: "Cox's Bazar District, Chattogram Division, Bangladesh" is tried part by part, the first part first,
    //     and the first part that has hotels wins; spelling, "District/Division/City" and apostrophes do not matter
    //  Nothing here ever writes to the database.
    // ══════════════════════════════════════════════════════

    public function resolvePlaceIds(array $params): array
    {
        $location = trim((string) ($params['location'] ?? ''));
        $index    = $this->areaIndex();
        $counts   = $this->hotelCounts();

        if (!empty($params['place_id'])) {
            $ids = $this->placeIdsUnder((string) ($params['place_type'] ?? 'place'), (int) $params['place_id'], $index);

            if ($this->sumHotels($ids, $counts) > 0 || $location === '') {
                return $ids;
            }
        } elseif ($location === '') {
            return [];
        }

        $sets = [];
        foreach (array_filter(array_map('trim', explode(',', $location))) as $part) {
            if ($ids = $this->placeIdsForName($part, $index)) {
                $sets[] = $ids;
            }
        }

        // what ALL the parts say together ("Beta's Bazar, Zedland" is the one in Zedland) ...
        if (count($sets) > 1) {
            $together = array_values(array_intersect(...$sets));

            if ($this->sumHotels($together, $counts) > 0) {
                return $together;
            }
        }

        // ... otherwise the first part that has hotels
        foreach ($sets as $ids) {
            if ($this->sumHotels($ids, $counts) > 0) {
                return $ids;
            }
        }

        return [];
    }

    /** Where a wording is on the map (nothing is stored), so the hotels near it can be found; null when the map does not know it */
    public function geocode(string $text): ?array
    {
        $text = trim($text);
        if (mb_strlen($text) < 3) return null;

        $key = 'location.geocode.' . md5(mb_strtolower($text));
        $hit = Cache::get($key);
        if ($hit !== null) return $hit === false ? null : $hit;

        try {
            $response = $this->nominatimClient(5)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $text, 'format' => 'json', 'limit' => 1, 'accept-language' => 'en',
            ])->json();
        } catch (\Throwable $e) {
            Log::warning('Nominatim geocode failed: ' . $e->getMessage());

            return null;   // not remembered: the next search tries again
        }

        $first = is_array($response) ? ($response[0] ?? null) : null;
        $geo   = $first && isset($first['lat'], $first['lon']) ? ['lat' => (float) $first['lat'], 'long' => (float) $first['lon']] : null;

        Cache::put($key, $geo ?? false, $geo ? now()->addDay() : now()->addMinutes(30));

        return $geo;
    }

    // ── the areas, by name, in memory ────────────
    private function areaIndex(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $idx = ['countries' => [], 'states' => [], 'cities' => [], 'places' => [],
            'cityPlaces' => [], 'stateCities' => [], 'countryStates' => [], 'statesByNorm' => [], 'citiesByNorm' => []];

        foreach (Country::query()->get(['id', 'name']) as $k) {
            $idx['countries'][$k->id] = ['name' => $k->name, 'norm' => $this->normalizeName((string) $k->name)];
        }

        foreach (State::query()->get(['id', 'name', 'country_id']) as $s) {
            $norm = $this->normalizeName((string) $s->name);
            $idx['states'][$s->id] = ['name' => $s->name, 'norm' => $norm, 'country_id' => $s->country_id];
            $idx['countryStates'][$s->country_id][] = $s->id;
            $idx['statesByNorm'][$norm][] = $s->id;
        }

        foreach (City::query()->get() as $c) {
            $norm = $this->normalizeName((string) $c->name);
            $idx['cities'][$c->id] = ['name' => $c->name, 'norm' => $norm, 'state_id' => $c->state_id, 'lat' => $c->lat ?? null, 'long' => $c->long ?? null];
            $idx['stateCities'][$c->state_id][] = $c->id;
            $idx['citiesByNorm'][$norm][] = $c->id;
        }

        foreach (Place::query()->get() as $p) {
            $idx['places'][$p->id] = ['name' => $p->name, 'norm' => $this->normalizeName((string) $p->name), 'city_id' => $p->city_id,
                'lat' => $p->lat, 'long' => $p->long, 'external_id' => $p->external_id, 'source' => $p->external_source ?? 'local'];
            if ($p->city_id) {
                $idx['cityPlaces'][$p->city_id][] = $p->id;
            }
        }

        return $this->index = $idx;
    }

    /**
     * The places under one area. Our older data keeps "Cox's Bazar" as a division AND as a district (and a division
     * is listed under another one), so an area also takes the rows of the same name in the same country.
     */
    private function placeIdsUnder(string $type, int $id, array $index): array
    {
        $stateIds = [];
        $cityIds  = [];

        if ($type === 'place') {
            return [$id];
        } elseif ($type === 'city' && isset($index['cities'][$id])) {
            $cityIds  = [$id];
            $norm     = $index['cities'][$id]['norm'];
            $country  = $index['states'][$index['cities'][$id]['state_id']]['country_id'] ?? null;

            foreach ($index['citiesByNorm'][$norm] ?? [] as $namesake) {
                if (($index['states'][$index['cities'][$namesake]['state_id']]['country_id'] ?? null) === $country) {
                    $cityIds[] = $namesake;
                }
            }
            foreach ($index['statesByNorm'][$norm] ?? [] as $namesake) {
                if ($index['states'][$namesake]['country_id'] === $country) {
                    $stateIds[] = $namesake;
                }
            }
        } elseif ($type === 'state' && isset($index['states'][$id])) {
            $stateIds = [$id];
        } elseif ($type === 'country' && isset($index['countries'][$id])) {
            $stateIds = $index['countryStates'][$id] ?? [];
        }

        return $this->placeIdsOfAreas($stateIds, $cityIds, $index);
    }

    /** The places under these divisions (and their districts) and under these districts */
    private function placeIdsOfAreas(array $stateIds, array $cityIds, array $index): array
    {
        $stateIds = array_values(array_unique($stateIds));

        // a division also takes the divisions named like one of its districts
        foreach ($stateIds as $sid) {
            $country = $index['states'][$sid]['country_id'];

            foreach ($index['stateCities'][$sid] ?? [] as $cid) {
                foreach ($index['statesByNorm'][$index['cities'][$cid]['norm']] ?? [] as $namesake) {
                    if ($index['states'][$namesake]['country_id'] === $country) {
                        $stateIds[] = $namesake;
                    }
                }
            }
        }

        foreach (array_unique($stateIds) as $sid) {
            $cityIds = array_merge($cityIds, $index['stateCities'][$sid] ?? []);
        }

        $placeIds = [];
        foreach (array_unique($cityIds) as $cid) {
            $placeIds = array_merge($placeIds, $index['cityPlaces'][$cid] ?? []);
        }

        return array_values(array_unique($placeIds));
    }

    /** The places of every area (place, district, division, country) that carries this wording as its name */
    private function placeIdsForName(string $wording, array $index): array
    {
        $n = $this->normalizeName($wording);
        if ($n === '') return [];

        // the rows named exactly so; when there is none, the rows containing it (3 letters at least)
        $match = function (array $rows) use ($n) {
            $exact = array_keys(array_filter($rows, fn ($r) => $r['norm'] === $n));

            return $exact ?: (mb_strlen($n) >= 3 ? array_keys(array_filter($rows, fn ($r) => str_contains($r['norm'], $n))) : []);
        };

        $ids = $match($index['places']);

        foreach ($match($index['cities']) as $cid) {
            $ids = array_merge($ids, $this->placeIdsUnder('city', $cid, $index));
        }
        foreach ($match($index['states']) as $sid) {
            $ids = array_merge($ids, $this->placeIdsUnder('state', $sid, $index));
        }
        foreach ($match($index['countries']) as $kid) {
            $ids = array_merge($ids, $this->placeIdsUnder('country', $kid, $index));
        }

        return array_values(array_unique($ids));
    }

    /** How many hotels a guest can find in each place (published, with a room: the same rule the search uses), by place id */
    private function hotelCounts(): array
    {
        return Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->whereHas('rooms')
            ->whereNotNull('place_id')
            ->select('place_id', DB::raw('count(*) as hotels'))
            ->groupBy('place_id')
            ->pluck('hotels', 'place_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    private function sumHotels(array $placeIds, array $counts): int
    {
        $sum = 0;
        foreach ($placeIds as $id) {
            $sum += $counts[$id] ?? 0;
        }

        return $sum;
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

        $this->index = null;

        return ['country' => $country, 'state' => $state, 'city' => $city];
    }

    /** The first row whose name matches once "Division" / "District" etc. and spelling variants are ignored */
    private function matchByName($query, string $name)
    {
        $wanted = $this->normalizeName($name);

        return $query->get()->first(fn ($row) => $this->normalizeName($row->name) === $wanted);
    }

    /**
     * A name the way the map, our data and a guest at a keyboard may all write it:
     * "Cox's Bazar District", "Coxs Bazar", "Cox Bazar" and "coxbazar" are all "cox bazar"; "Chattogram Division" is "chittagong".
     */
    public function normalizeName(string $name): string
    {
        $n = mb_strtolower(trim($name));
        $n = preg_replace("/['’`ʼ]s\b/u", '', $n);          // Cox's → Cox
        $n = preg_replace("/['’`ʼ]/u", '', $n);
        $n = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $n);   // commas, dots, dashes
        $n = preg_replace('/\b(division|district|zila|zilla|city|metropolitan)\b/u', ' ', $n);

        // Spellings the map and our own data disagree on
        foreach ([
            'chattogram' => 'chittagong', 'chattagram' => 'chittagong', 'chittagram' => 'chittagong',
            'barisal' => 'barishal', 'comilla' => 'cumilla', 'bogra' => 'bogura', 'jessore' => 'jashore', 'dacca' => 'dhaka',
            'coxbazar' => 'cox bazar', 'coxsbazar' => 'cox bazar', 'coxsbazaar' => 'cox bazar', 'bazaar' => 'bazar',
            'sylet' => 'sylhet',
        ] as $from => $to) {
            $n = preg_replace('/\b' . $from . '\b/u', $to, $n);
        }

        // "Betas" and "Beta's" are one word to a guest at a keyboard: a closing s is left out (on both sides of every comparison)
        $n = preg_replace('/\b(\p{L}{3,})s\b/u', '$1', $n);

        return trim(preg_replace('/\s+/u', ' ', $n));
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
}
