<?php

namespace App\Http\Controllers\API\Portal\Location;

use App\Http\Controllers\BaseController;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends BaseController
{
    public function __construct(
        private LocationService $locationService
    ) {}

    public function suggestions(
        Request $request
    ): JsonResponse
    {
        $results = $this->locationService
            ->fetchNominatimSuggestions(
                $request->input('q')
            );

        return $this->sendSuccess($results);
    }

    public function resolvePlace(
        Request $request
    ): JsonResponse
    {
        $place = $this->locationService
            ->resolvePlaceIds($request->all());

        return $this->sendSuccess($place);
    }
}
