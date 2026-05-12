<?php

namespace App\Services;

use App\Models\City;
use App\Models\Place;
use App\Models\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * Resolve place IDs from params
     */
    public function resolvePlaceIds(array $params): array
    {
        if (!empty($params['place_id'])) {
            return $this->resolvePlaceIdsBySelection(
                (int) $params['place_id'],
                $params['place_type'] ?? 'place'
            );
        }

        if (empty($params['location'])) {
            return [];
        }

        return $this->resolvePlaceIdsByText(
            trim($params['location'])
        );
    }

    /**
     * User selected suggestion
     */
    public function resolvePlaceIdsBySelection(int $id, string $type): array
    {
        return match ($type) {

            'city' => Place::where('city_id', $id)
                ->pluck('id')
                ->toArray(),

            'state' => Place::whereHas('city', function ($q) use ($id) {
                $q->whereHas('state', function ($s) use ($id) {
                    $s->where('id', $id);
                });
            })->pluck('id')->toArray(),

            default => [$id],
        };
    }

    /**
     * Resolve location text from local DB
     */
    public function resolvePlaceIdsByText(string $location): array
    {
        $placeIds = Place::where('name', 'like', "%{$location}%")
            ->pluck('id')
            ->toArray();

        // Cities
        $cityIds = City::where('name', 'like', "%{$location}%")
            ->pluck('id');

        if ($cityIds->isNotEmpty()) {
            $placeIds = array_merge(
                $placeIds,
                Place::whereIn('city_id', $cityIds)
                    ->pluck('id')
                    ->toArray()
            );
        }

        // States
        $stateIds = State::where('name', 'like', "%{$location}%")
            ->pluck('id');

        if ($stateIds->isNotEmpty()) {

            $stateCityIds = City::whereIn('state_id', $stateIds)
                ->pluck('id');

            $placeIds = array_merge(
                $placeIds,
                Place::whereIn('city_id', $stateCityIds)
                    ->pluck('id')
                    ->toArray()
            );
        }

        return array_unique(array_values($placeIds));
    }

    /**
     * Resolve from Nominatim and store locally
     */
    public function resolveOrCreateFromNominatim(string $query): ?Place
    {
        try {

            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . '/1.0',
            ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                    'accept-language' => 'en'
                ])
                ->json();

            if (empty($response)) {
                return null;
            }

            return $this->storeNominatimResult($response[0]);

        } catch (\Exception $e) {

            Log::warning(
                'Nominatim resolve failed: ' . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Fetch suggestions from Nominatim
     */
    public function fetchNominatimSuggestions(string $query): array
    {
        try {

            $response = Http::withHeaders([
                'User-Agent' => config('app.name') . '/1.0',
            ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 5,
                    'addressdetails' => 1,
                    'accept-language' => 'en'
                ])
                ->json();

            return collect($response)->map(function ($r) {

                return [
                    'type' => 'place',
                    'id' => null,
                    'external_id' => (string) $r['place_id'],
                    'name' => $r['display_name'],
                    'city' => $r['address']['city']
                        ?? $r['address']['town']
                            ?? $r['address']['village']
                            ?? null,
                    'state' => $r['address']['state'] ?? null,
                    'country' => $r['address']['country'] ?? null,
                    'lat' => (float) $r['lat'],
                    'long' => (float) $r['lon'],
                    'full_address' => $r['display_name'],
                    'source' => 'nominatim',
                ];

            })->toArray();

        } catch (\Exception $e) {

            Log::warning(
                'Nominatim suggestions failed: ' . $e->getMessage()
            );

            return [];
        }
    }

    /**
     * Store Nominatim result locally
     */
    public function storeNominatimResult(array $r): Place
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

            [
                'external_id' => (string) $r['place_id']
            ],

            [
                'city_id' => $city?->id,
                'name' => $r['display_name'],
                'lat' => $r['lat'],
                'long' => $r['lon'],
                'external_source' => 'nominatim',
                'full_address' => $r['display_name'],
            ]
        );
    }

    /**
     * Format Place model
     */
    public function formatPlace(Place $p): array
    {
        return [
            'type' => 'place',
            'id' => $p->id,
            'external_id' => $p->external_id,
            'name' => $p->name,
            'city' => $p->city?->name,
            'state' => $p->city?->state?->name,
            'country' => $p->city?->state?->country?->name,
            'lat' => $p->lat,
            'long' => $p->long,
            'source' => $p->external_source ?? 'local',
        ];
    }
}
