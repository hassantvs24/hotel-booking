<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Portal\RoomRequest  as RoomRequestForm;
use App\Models\RoomRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomRequestController extends BaseController
{
    public function roomRequest(RoomRequestForm $request): JsonResponse
    {
        $requestData = $request->validated();
        $data = RoomRequest::updateOrCreate(
            [
                'room_id' => $requestData['room_id'],
                'user_id' => $requestData['user_id']
            ],
            $requestData
        );
        return $this->sendSuccess($data, 'Room request has been created or updated successfully.');
    }
}
