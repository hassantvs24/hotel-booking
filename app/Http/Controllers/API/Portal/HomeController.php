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

        $properties = Property::with(['primaryImage', 'facilities', 'place.city'])->limit(9)->get();
        $places = Place::with('city')
            ->latest()
            ->whereHas('properties', function ($query) {
                $query->where('status', Property::STATUS_PUBLISHED);
            })
            ->limit($allowedSlider)->get();

        $data = [
            'properties'  => $properties,
            'places' => $places
        ];

        return $this->sendSuccess($data);
    }
}
