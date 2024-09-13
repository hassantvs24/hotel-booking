<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\BookingAccepted;
use App\Models\BookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RequestController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $requestQuerry = $request->all();
        $data = BookingRequest::updateOrCreate(
            [
                'user_id' => $requestQuerry['user_id'],
            ],
            array_merge($requestQuerry, ['request_expiration_time' => Carbon::now()->addMinutes(2)])
        );

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
