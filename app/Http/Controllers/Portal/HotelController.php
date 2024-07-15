<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Property;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function index(): View
    {
        $hotelsResponded = Property::count();
        $properties = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->get();

        return view('portal.hotel-search.index', compact('properties', 'hotelsResponded'));
    }


    public function show(Place $place, $slug): View
    {
        $properties = Property::query()
            ->where([
                'place_id' => $place->id,
                'status'   => Property::STATUS_PUBLISHED,
            ])->get();
        return view('portal.hotel.index', compact('properties'));
    }

    public function hotelDetails(Property $property, $slug)
    {
        return view('portal.hotel-details.index', compact('property'));
    }
}
