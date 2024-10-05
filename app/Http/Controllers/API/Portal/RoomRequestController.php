<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Portal\RoomRequest  as RoomRequestForm;
use App\Models\Property;
use App\Models\RoomRequest;
use App\Models\RoomRequestAccepted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RoomRequestController extends BaseController
{
    public function roomRequest(RoomRequestForm $request): JsonResponse
    {
        $requestData = $request->validated();

        $existingRequest = RoomRequest::where([
            'room_id' => $requestData['room_id'],
            'user_id' => $requestData['user_id'],
            'property_id' => $requestData['property_id'],
        ])->first();

        if($existingRequest){
            $data = RoomRequest::updateOrCreate(
                [
                    'room_id' => $requestData['room_id'],
                    'user_id' => $requestData['user_id'],
                    'property_id' => $requestData['property_id'],

                ],
                $requestData
            );
        }
        else{
            $data = RoomRequest::updateOrCreate(
                [
                    'room_id' => $requestData['room_id'],
                    'user_id' => $requestData['user_id'],
                    'property_id' => $requestData['property_id'],

                ],
                array_merge($requestData, ['request_expiration_time' => Carbon::now()->addHour(24)])
            );
        }

        return $this->sendSuccess($data, 'Room request has been created or updated successfully.');
    }



    public function roomRequestNotification(Request $request): JsonResponse
    {
        $roomRequests = RoomRequest::where('user_id', $request->user()->id)     // Fetch the room requests with eager-loaded room and property relations
            ->with('room.property') // Eager load the related property
            ->get()
            ->map(function ($roomRequest) {
                return [
                    'property_id' => $roomRequest->room->property->id,
                    'property_name' => $roomRequest->room->property->name, // Replace with your property attributes
                    'request_expiration_time' => $roomRequest->request_expiration_time,
                ];
            });
        $uniqueProperties = $roomRequests->groupBy('property_id')->map(function ($requests) {   // Group by property_id and fetch the oldest request_expiration_time for each unique property
            return $requests->sortBy('request_expiration_time')->first(); // Sort ascending to get the oldest
        });

        $data = [
            'properties' => $uniqueProperties->values()->all()        // Prepare response data
        ];

        return response()->json($data, 200);
    }


    public function roomResponselist(Request $request, $propertyId)
    {

        $roomRequests = RoomRequest::where('user_id', $request->user()->id)      // Fetch all room requests for the given property and user
            ->where('property_id', $propertyId)
            ->get();
        $totalRequests = $roomRequests->count();        // Count the total number of room requests submitted for the property

        $approvedRequests = $roomRequests->where('status', 'Approved')->count();        // Count how many of those requests were approved

        $roomRequest = RoomRequestAccepted::whereHas('room_request', function ($query) use ($request, $propertyId) {   // Fetch room responses based on approved room requests
                $query->where('user_id', $request->user()->id)
                      ->where('status', 'Approved')
                      ->where('property_id', $propertyId);
            })
            ->with(['room_request', 'room_request.room', 'room_request.room.primaryImage', 'room_request.room.property.facilities', 'room_request.room.property.place.city'])
            ->get();

        $data = [
            'total_requests' => $totalRequests,
            'approved_requests' => $approvedRequests,
            'room_request' => $roomRequest,
        ];

        return $this->sendSuccess($data);
    }









}
