<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Property;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
            ->with(['images', 'facilities', 'place.city'])
            ->get();

        return $this->sendSuccess($properties);
    }

    private function getPropertiesByLocation($properties, $location)
    {
        $properties = $properties->where(function ($query) use ($location) {
            $query->whereHas('place', function ($q) use ($location) {
                $q->where('name', 'like', '%' . $location . '%')
                    ->orWhereHas('city', function ($q) use ($location) {
                        $q->where('name', 'like', '%' . $location . '%')
                            ->orWhereHas('state', function ($q) use ($location) {
                                $q->where('name', 'like', '%' . $location . '%')
                                    ->orWhereHas('country', function ($q) use ($location) {
                                        $q->where('name', 'like', '%' . $location . '%');
                                    });
                            });
                    });
            });
        });

        return $properties;
    }
}
