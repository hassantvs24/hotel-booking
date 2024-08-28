<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $requestQuerry = $request->all();
        $data = BookingRequest::create($requestQuerry);

        return $this->sendSuccess($data);
    }

    public function property_list()
    {
        $data = BookingAccepted::whereHas('bookingRequest', function ($query) {
            $query->where('user_id', 1);
        })->with(['bookingRequest', 'property', 'property.facilities', 'property.place.city'])->get();

        return $this->sendSuccess($data);
    }
}
