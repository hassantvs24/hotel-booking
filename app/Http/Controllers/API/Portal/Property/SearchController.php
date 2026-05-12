<?php

namespace App\Http\Controllers\API\Portal\Property;

use App\Http\Controllers\BaseController;
use App\Models\City;
use App\Models\Place;
use App\Models\State;
use App\Services\PropertySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchController extends BaseController
{
    public function __construct(
        private PropertySearchService $propertySearchService
    ) {}

    // =====================================================
    // MAIN SEARCH
    // =====================================================

    public function search(Request $request): JsonResponse
    {
        $properties = $this->propertySearchService
            ->search($request->all());

        return $this->sendSuccess($properties);
    }
}
