<?php

namespace App\Http\Controllers\API\Portal;

use App\Http\Controllers\BaseController;
use App\Models\Place;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class HomeController extends BaseController
{
    public function index() : JsonResponse
    {
        $allowedSlider = getSetting('home_page_number_of_slider');

        $rooms = Room::with('property.place')->limit(9)->get();
        $places = Place::with('city')
            ->latest()
            ->limit($allowedSlider)->get();

        $data = [
            'rooms'  => $rooms,
            'places' => $places
        ];

        return $this->sendSuccess($data);
    }
}
