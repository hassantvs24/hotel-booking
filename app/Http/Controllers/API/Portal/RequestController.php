<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RequestController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $requestQuery = $request->all();
        $existingRequest = BookingRequest::where('user_id', $request->user()->id)->first(); // Find existing booking request by user_id
    
        if ($existingRequest) {
            $data = $existingRequest;                   // If the record exists, simply return it without updating
        } else {
            $data = BookingRequest::create(
                array_merge($requestQuery, [
                    'request_expiration_time' => Carbon::now()->addHour(24),
                    'status' => 'Pending',
                    'user_id' => $request->user()->id
                ])
            );
        }
    
        return $this->sendSuccess($data);
    }
    
    

    public function property_list(Request $request) : JsonResponse
    {    
        // Fetch all the accepted booking requests with related data and expiration time
        $acceptedProperties = BookingAccepted::whereHas('bookingRequest', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
                  ->where('status', 'Approved');
        })->with([
            'bookingRequest', 
            'property', 
            'property.facilities', 
            'property.place.city'
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
