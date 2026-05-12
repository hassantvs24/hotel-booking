<?php

namespace App\Http\Controllers\API\Portal\Property;

use App\Http\Controllers\BaseController;
use App\Models\FacilitySub;
use App\Models\PropertyCategory;
use App\Services\PropertySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends BaseController
{
    public function __construct(
        private PropertySearchService $propertySearchService
    ) {}

    // =====================================================
    // FILTER OPTIONS
    // =====================================================

    public function getFilters(): JsonResponse
    {
        $facilities = FacilitySub::select(
            'id',
            'name'
        )->get();

        $hotelClasses = [
            '7 Stars',
            '6 Stars',
            '5 Stars',
            '4 Stars',
            '3 Stars',
            '2 Stars',
            '1 Star',
            'Unrated'
        ];

        $ratings = [1, 2, 3, 4, 5];

        $propertyTypes = PropertyCategory::select(
            'id',
            'name'
        )->get();

        $sortOptions = [
            [
                'value' => 'rating',
                'name'  => 'Top Rated'
            ],
            [
                'value' => 'asc',
                'name'  => 'Price: Low to High'
            ],
            [
                'value' => 'desc',
                'name'  => 'Price: High to Low'
            ],
            [
                'value' => 'nearest',
                'name'  => 'Nearest First'
            ],
        ];

        return $this->sendSuccess([
            'facilities'    => $facilities,
            'hotelClasses'  => $hotelClasses,
            'ratings'       => $ratings,
            'propertyTypes' => $propertyTypes,
            'sortOptions'   => $sortOptions,
        ]);
    }

    // =====================================================
    // SEARCH FILTER
    // =====================================================

    public function getFilteredProperties(
        Request $request
    ): JsonResponse
    {
        $properties = $this->propertySearchService
            ->search($request->all());

        return $this->sendSuccess([
            'properties' => $properties
        ]);
    }

    // =====================================================
    // REQUEST FILTER
    // =====================================================

    public function getFilteredPropertiesByRequest(
        Request $request
    ): JsonResponse
    {
        $params = $request->all();

        $properties = $this->propertySearchService
            ->search($params);

        $userId = $request->user()->id;

        $properties = $properties->filter(
            function ($property) use ($userId) {

                return $property
                    ->bookingAccepteds()
                    ->whereHas(
                        'bookingRequest',
                        function ($q) use ($userId) {

                            $q->where('user_id', $userId)
                                ->where(
                                    'status',
                                    'Approved'
                                );
                        }
                    )
                    ->exists();
            }
        )->values();

        return $this->sendSuccess([
            'properties' => $properties,
            'accepted_properties_count'
            => $properties->count(),
        ]);
    }
}
