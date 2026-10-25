<?php

namespace App\Http\Controllers\API\Portal\Location;

use App\Http\Controllers\BaseController;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LocationController
 *
 * Handles all geo/place HTTP concerns.
 * All logic delegated to LocationService.
 *
 * Routes:
 *   GET  /portal/location/suggestions   → suggestions()
 *   POST /portal/location/resolve-place → resolvePlace()
 */
class LocationController extends BaseController
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    // ── Typeahead dropdown ─────────────────────────────────
    // GET /portal/location/suggestions?q=Cox
    public function suggestions(Request $request): JsonResponse
    {
        $results = $this->locationService->getSuggestions(
            trim($request->input('q', '')),
            $request->boolean('listing')   // the owner's property form wants areas by their places, a guest wants areas with hotels
        );

        return $this->sendSuccess($results);
    }

    // ── Map result → place + country/division/district, all as ids ──
    // POST /portal/location/resolve   (signed-in owners filling the property form)
    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'external_id'  => 'required|string|max:60',
            'name'         => 'required|string|max:255',
            'lat'          => 'required|numeric',
            'long'         => 'required|numeric',
            'city'         => 'nullable|string|max:255',
            'district'     => 'nullable|string|max:255',
            'state'        => 'nullable|string|max:255',
            'country'      => 'nullable|string|max:255',
            'kind'         => 'nullable|string|max:40',
            'full_address' => 'nullable|string',
        ]);

        return $this->sendSuccess($this->locationService->resolveLocation($data));
    }

    // ── Store Nominatim place → return local place_id ──────
    // POST /portal/location/resolve-place
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

        $place = $this->locationService->resolveAndStorePlace(
            $request->only([
                'external_id', 'name', 'lat', 'long',
                'city', 'state', 'country', 'full_address',
            ])
        );

        return $this->sendSuccess(
            $this->locationService->formatPlace($place)
        );
    }
}
