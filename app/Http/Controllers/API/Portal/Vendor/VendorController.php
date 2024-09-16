<?php

namespace App\Http\Controllers\API\Portal\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\Country;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Repositories\Property\PropertyRepository;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Portal\Vendor\VerdorRequest;
use App\Traits\MediaMan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends BaseController
{
    use MediaMan;
    public function allPropertyOption(): JsonResponse
    {
        $countries = Country::all();
        $propertyTypes = PropertyCategory::all();

        $currencies = [
            [
                'name' => 'Bangladesh Taka',
                'code' => 'BDT',
            ],
            [
                'name' => 'Afghan Afghani',
                'code' => 'AFN',
            ],
            [
                'name' => 'US Dollar',
                'code' => 'USD',
            ],
        ];

        $data = [
            'countries' => $countries,
            'propertyTypes' => $propertyTypes,
            'currencies' => $currencies
        ];

        return $this->sendSuccess($data);
    }

    public function store(VerdorRequest $request): JsonResponse
    {

        $property = Property::create(array_merge(
            $request->except(['photo']),
            ['status' => 'Pending']
        ));

        if ($request->hasFile('photo')) {
            $image = $this->storeFile($request->file('photo'), 'properties');
            $property->primaryImage()->create([...$image, 'media_role' => 'property_image']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Property created successfully!',
            'property' => $property->load('primaryImage')
        ], 201);
    }

    public function allproperties(Request $request): JsonResponse
    {
        $properties = Property::where('user_id', $request->user()->id)->get();

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data, 'Properties fetched successfully!');
    }
}
