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
            return $this->sendError('No properties available at this address');
        }

        $userId = $request->user()->id;
        $existingRequest = BookingRequest::where('user_id', $userId)->first();

        if ($existingRequest) {
            return $this->sendSuccess($existingRequest);
        }

        // Create only one BookingRequest
        $bookingRequest = BookingRequest::create([
            'request_expiration_time' => Carbon::now()->addHour(24),
            'status' => 'Pending',
            'user_id' => $userId,
            'search_name' => $request->input('search_name'),
            'checkin' => $request->input('checkin'),
            'checkout' => $request->input('checkout'),
            'adult' => $request->input('adult'),
            'children' => $request->input('children') ?? 0,
        ]);

        return $this->sendSuccess($bookingRequest);
    }


    public function property_list(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $properties = Property::whereHas('bookingAccepteds', function ($query) use ($userId) {
            $query->whereHas('bookingRequest', function ($subQuery) use ($userId) {
                $subQuery->where('user_id', $userId)
                    ->whereIn('status', ['Approved']);
            });
        })
            ->with([
                'images',
                'facilities',
                'place.city',
                'rooms',
                'rooms.images',
                'bookingAccepteds'
            ])
            ->get();

        $propertyCount = $properties->count();

        $data = [
            'properties' => $properties,  // Directly fetches properties as the parent object
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
