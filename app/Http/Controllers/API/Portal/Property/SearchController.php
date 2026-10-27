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
        $request->validate(PropertySearchService::rules(), PropertySearchService::messages());

        $result = $this->searchService->searchPage(
            $request->all(),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 12)
        );

        return $this->sendSuccess([
            'properties' => $result['items'],   // this page's hotels, in full
            'pins'       => $result['pins'],    // every matching hotel, small (the map)
            'total'      => $result['total'],
            'page'       => $result['page'],
            'per_page'   => $result['per_page'],
            'has_more'   => $result['has_more'],
        ]);
    }
}
