<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Place;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class HomeController extends BaseController
{
    public function index(): JsonResponse
    {
        $allowedSlider = getSetting('home_page_number_of_slider');

        // "Best rated picks": published hotels a guest can book, the best scored first (the guests' score once there are reviews)
        $properties = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->whereHas('rooms')
            ->with(['primaryImage', 'facilities', 'place.city', 'rooms'])
            ->orderByRaw('IF(reviews_count > 0, reviews_avg_rating, rating) DESC')
            ->limit(9)
            ->get()
            ->each->makeHidden(array_merge(Property::PRIVATE_FOR_GUESTS, ['phone_number', 'rooms', 'refund_policy']));
        $places = Place::with('city')
            ->latest()
            ->whereHas('properties', function ($query) {
                $query->where('status', Property::STATUS_PUBLISHED);
            })
            ->limit($allowedSlider)->get();

        $data = [
            'properties'  => $properties,
            'places' => $places,
            // the real limits of an offer, so what the page tells guests never drifts from the rules
            'bidding' => [
                'max_bids'      => \App\Models\RoomRequest::MAX_BIDS,
                'pending_hours' => (int) config('bidding.pending_hours', 8),
                'payment_hours' => (int) config('bidding.payment_hours', 2),
            ],
        ];

        return $this->sendSuccess($data);
    }
}
