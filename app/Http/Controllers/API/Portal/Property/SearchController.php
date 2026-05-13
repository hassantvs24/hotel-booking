<?php

namespace App\Http\Controllers\API\Portal\Property;

use App\Http\Controllers\BaseController;
use App\Services\PropertySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SearchController
 *
 * Handles initial property search only.
 * Geo/place concerns moved to LocationController.
 * All query logic delegated to PropertySearchService.
 *
 * Routes:
 *   GET /portal/search → search()
 */
class SearchController extends BaseController
{
    public function __construct(
        protected PropertySearchService $searchService
    ) {}

    // ── Initial property search (page load from SearchBox) ──
    public function search(Request $request): JsonResponse
    {
        $properties = $this->searchService->searchProperties(
            $request->all()
        );

        return $this->sendSuccess($properties);
    }
}
