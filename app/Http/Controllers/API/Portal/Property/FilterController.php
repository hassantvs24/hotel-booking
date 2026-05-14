<?php

namespace App\Http\Controllers\API\Portal\Property;

use App\Http\Controllers\BaseController;
use App\Services\PropertySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FilterController
 *
 * Filter options + filtered property search.
 *
 * Routes:
 *   GET /portal/filter                    → getFilters()
 *   GET /portal/filter/search-properties  → getFilteredProperties()
 *   GET /portal/filter/request-properties → getFilteredPropertiesByRequest() [auth]
 */
class FilterController extends BaseController
{
    public function __construct(
        protected PropertySearchService $searchService
    ) {}

    // ── Filter dropdown options ────────────────────────────
    public function getFilters(): JsonResponse
    {
        return $this->sendSuccess(
            $this->searchService->getFilterOptions()
        );
    }

    // ── Search page — filter form submit ───────────────────
    public function getFilteredProperties(Request $request): JsonResponse
    {
        $properties = $this->searchService->searchProperties(
            $request->all()
        );

        return $this->sendSuccess(['properties' => $properties]);
    }

    // ── Request page — filter form submit [auth:sanctum] ───
    public function getFilteredPropertiesByRequest(Request $request): JsonResponse
    {
        $properties = $this->searchService->searchProperties(
            $request->all(),
            $request->user()->id
        );

        return $this->sendSuccess([
            'properties'               => $properties,
            'accepted_properties_count' => $properties->count(),
        ]);
    }
}
