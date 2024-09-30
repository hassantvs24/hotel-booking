<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends BaseController
{
    public function search(Request $request) : JsonResponse
    {
        $searchQuery = $request->all();

        $properties = Property::query();

        if (isset($searchQuery['location'])) {
            $properties = $this->getPropertiesByLocation($properties, $searchQuery['location']);
        }

        $properties = $properties->where('status', Property::STATUS_PUBLISHED)
            ->with(['images', 'facilities', 'place.city','rooms'])
            ->get();

        return $this->sendSuccess($properties);
    }

    private function getPropertiesByLocation($properties, $location)
    {
        $properties = $properties->where(function ($query) use ($location) {
            $query->whereHas('place', function ($q) use ($location) {
                $q->where('name', 'like', '%' . $location . '%');
            })->orWhereHas('place.city', function ($q) use ($location) {
                $q->where('name', 'like', '%' . $location . '%');
            })->orWhereHas('place.city.state', function ($q) use ($location) {
                $q->where('name', 'like', '%' . $location . '%');
            })->orWhereHas('place.city.state.country', function ($q) use ($location) {
                $q->where('name', 'like', '%' . $location . '%');
            });
        });

        return $properties;
    }
}
