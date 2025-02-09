<?php

namespace App\Http\Controllers\API\Portal\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\Country;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Repositories\Property\PropertyRepository;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Portal\Vendor\VerdorRequest;
use App\Models\Facility;
use App\Models\PropertyRequest;
use App\Models\PropertyRule;
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

    public function store(Request $request): JsonResponse
    {

        $requestData = $request->all();

        $dataToStore = array_merge(
            $request->except([
                'images',
                'facilities',
                'rules',
                'propertyName',
                'propertyType',
                'address',
                'apartment',
                'country',
                'city',
                'bankName',
                'accountNumber',
                'ifscCode',
                'accountName',
                'bankBranch',
                'routingNumber',
                'swiftCode',
                'iban',
                'postCode',
                'rooms',
                'ref_id',
                'user_id'
            ]),
            [
                'property_type' => $requestData['propertyType'],
                'address' => [
                    'address' => $requestData['address'],
                    'apartment' => $requestData['apartment'],
                    'country' => $requestData['country'],
                    'city' => $requestData['city'],
                ],
                'zip_code' => $requestData['postCode'],
                'total_room' => $requestData['rooms'],
                'bank_details' => [
                    'bankName' => $requestData['bankName'],
                    'accountNumber' => $requestData['accountNumber'],
                    'ifscCode' => $requestData['ifscCode'],
                    'accountName' => $requestData['accountName'],
                    'bankBranch' => $requestData['bankBranch'],
                    'routingNumber' => $requestData['routingNumber'],
                    'swiftCode' => $requestData['swiftCode'],
                    'iban' => $requestData['iban']
                ],
                'status' => 'Pending',
                'user_id' => Auth::id()
            ]
        );

        $dataToStore['name'] = $requestData['propertyName'];
        $dataToStore['property_type'] = $requestData['propertyType'];

        $property = Property::create($dataToStore);

        $propertyRequest = PropertyRequest::where('unique_request_number', $requestData['ref_id'])->first();
        $propertyRequest->update([
            'property_id' => $property->id,
        ]);
        if (!empty($requestData['facilities'])) {
            $property->facilities()->attach($requestData['facilities']);
        }

        if(!empty($requestData['rules'])) {
            $property->rules()->attach($requestData['rules'],[
                'is_active' => true,
                'rule_description' => 'make your life easy',
            ]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $image = $this->storeFile($file, 'properties');
                $property->images()->create(array_merge($image, ['media_role' => 'property_gallery_image']));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Property created successfully!',
            'property' => $property
        ], 201);
    }

    public function requestProperties(Request $request): JsonResponse
    {
        // $properties = Property::where('user_id', $request->user()->id)->get();

        $properties = PropertyRequest::where('user_id', $request->user()->id)->get();

        $data = [
            'properties' => $properties
        ];

        return $this->sendSuccess($data, 'Properties fetched successfully!');
    }

    public function getRegInfo(): JsonResponse
    {
        $rules = PropertyRule::all();
        $facilities = Facility::all();

        $data = [
            'rules' => $rules,
            'facilities' => $facilities
        ];
        return $this->sendSuccess($data);
    }
}
