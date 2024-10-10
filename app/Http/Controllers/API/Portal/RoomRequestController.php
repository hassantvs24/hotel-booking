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

        $existingRequest = RoomRequest::withTrashed()->where([
            'room_id' => $requestData['room_id'],
            'user_id' => $requestData['user_id'],
            'property_id' => $requestData['property_id'],
        ])->first();

        if ($existingRequest) {
            if ($existingRequest->trashed()) {
                $existingRequest->restore();
                $existingRequest->update($requestData);
            } else {
                $existingRequest->update($requestData);
            }
        } else {
            $data = RoomRequest::create(
                array_merge($requestData, ['request_expiration_time' => Carbon::now()->addHour(24)])
            );
        }

        return $this->sendSuccess($existingRequest ?? $data, 'Room request has been created or updated successfully.');
    }

    public function roomRequestNotification(Request $request): JsonResponse
    {
        $roomRequests = RoomRequest::with('room.property')
            ->where('user_id', $request->user()->id)
            ->get();

        $uniqueProperties = [];        // Create a list to hold unique properties with the earliest expiration time

        foreach ($roomRequests as $roomRequest) {
            $propertyId = $roomRequest->room->property->id;

            if (!isset($uniqueProperties[$propertyId])) {                       // If this property is not already in our unique properties list, add it
                $uniqueProperties[$propertyId] = [
                    'property_id' => $propertyId,
                    'property_name' => $roomRequest->room->property->name,
                    'request_expiration_time' => $roomRequest->request_expiration_time,
                ];
            } else {
                $existingExpirationTime = $uniqueProperties[$propertyId]['request_expiration_time'];     // Update the expiration time if the current one is earlier
                if ($roomRequest->request_expiration_time < $existingExpirationTime) {
                    $uniqueProperties[$propertyId]['request_expiration_time'] = $roomRequest->request_expiration_time;
                }
            }
        }

        $data = [
            'properties' => array_values($uniqueProperties)
        ];
        return $this->sendSuccess($data);
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


    public function removeNotification(Request $request, $propertyId) : JsonResponse
    {
        $userId = $request->user()->id;
        $deletedCount = RoomRequest::where('property_id', $propertyId)
            ->where('user_id', $userId)
            ->delete();
        return $this->sendSuccess($deletedCount);
    }

    public function acceptedRoomlist()
    {
        RoomRequest::where
    }

}
