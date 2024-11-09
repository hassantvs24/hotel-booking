<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RequestController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->all();
        $address = $validated['search_name'];
        $matchingProperties = Property::where('address', 'LIKE', '%' . $address . '%')->get();

        if ($matchingProperties->isEmpty()) {
            return $this->sendError('No properties Available on this Address');
        }
        $userId = $request->user()->id;
        $existingRequest = BookingRequest::where('user_id', $userId)->first();
        if ($existingRequest) {
            return $this->sendSuccess($existingRequest);
        }
        $bookingRequests = $matchingProperties->map(function ($property) use ($request, $userId) {
            return BookingRequest::create([
                'request_expiration_time' => Carbon::now()->addHour(24),
                'status' => 'Pending',
                'user_id' => $userId,
                'search_name' => $request->input('search_name'),
                'checkin' => $request->input('checkin'),
                'checkout' => $request->input('checkout'),
                'adult' => $request->input('adult'),
                'children' => $request->input('children') ?? 0,
            ]);
        });

        return $this->sendSuccess($bookingRequests);
    }


    public function property_list(Request $request): JsonResponse
    {
        $acceptedProperties = BookingAccepted::whereHas('bookingRequest', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
                ->whereIn('status', ['Approved']);
        })->with([
            'bookingRequest',
            'property',
            'property.facilities',
            'property.place.city',
            'property.rooms'
        ])
            ->get(['id', 'request_expiration_time', 'property_id']);

        $propertyCount = $acceptedProperties->count();    // Count the number of properties that have accepted the request

        $data = [
            'accepted_properties' => $acceptedProperties,  // This includes the property data along with expiration time
            'accepted_properties_count' => $propertyCount,
        ];

        return $this->sendSuccess($data);
    }



    public function fetchTimer(Request $request): JsonResponse
    {
        $data = BookingRequest::where('user_id', $request->user()->id)->first();

        return $this->sendSuccess($data);
    }
}
