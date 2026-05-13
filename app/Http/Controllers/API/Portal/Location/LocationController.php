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
            trim($request->input('q', ''))
        );

        return $this->sendSuccess($results);
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
