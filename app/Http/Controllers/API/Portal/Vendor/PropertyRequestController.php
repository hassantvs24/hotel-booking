<?php

namespace App\Http\Controllers\API\Portal\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\PropertyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyRequestController extends BaseController
{
    public function store(Request $request) : JsonResponse
    {
        try {
            $data = $this->validateRequest($request);

            if (!$this->isAbleToSendRequest($request)) {
                return $this->sendError('You have already requested for this property', [], 400);
            }

            $propertyRequest = PropertyRequest::create($data);

            return $this->sendSuccess($propertyRequest, 'Property request created successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    private function isAbleToSendRequest(Request $request): bool
    {
        $maybeAlreadyRequested = PropertyRequest::where('owner_email', $request->owner_email)
            ->where('property_title', $request->property_title)
            ->where('status', PropertyRequest::STATUS_PENDING)
            ->first();

        return $maybeAlreadyRequested === null;
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string',
            'owner_email' => 'required|email',
            'contact_number' => 'required|string',
            'reason' => 'required|string',
            'property_title' => 'required|string',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zipcode' => 'nullable|string',
            'lowest_price' => 'nullable|numeric',
            'highest_price' => 'nullable|numeric',
        ]);
    }
}
