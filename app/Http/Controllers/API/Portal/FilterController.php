<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Facility;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends BaseController
{
    public function getFilters():JsonResponse
    {

        $facilities = Facility::get();
        $hotelClasses = ['7 Stars','6 Stars','5 Stars','4 Stars','3 Stars','2 Stars','1 Star','Unrated'];
        $ratings = [1, 2, 3, 4, 5];
        $propertyTypes = PropertyCategory::all();
        $sortOptions = [
            ['value' => 'asc', 'name' => 'Low to High'],
            ['value' => 'desc', 'name' => 'High to Low'],
        ];


        $data =[
            'facilities' => $facilities,
            'hotelClasses' => $hotelClasses,
            'ratings' => $ratings,
            'propertyTypes' => $propertyTypes,
            'sortOptions' => $sortOptions,
        ];

        return $this->sendSuccess($data);
    }

    public function getFilteredProperties(Request $request) : JsonResponse
    {
        $searchQuery = $request->all();

        $properties = Property::query();

        if (isset($searchQuery['location'])) {
            $properties = $this->getPropertiesByLocation($properties, $searchQuery['location']);
        }

        $properties = $properties->where('status', Property::STATUS_PUBLISHED)
            ->with(['images', 'facilities', 'place.city','rooms'])
            ->get();

            $data = [
                'properties' => $properties
            ];
        return $this->sendSuccess($data);
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

