<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function index(): View
    {
        $hotelsResponded = Property::count();
        $properties = Property::all();

        return view('portal.hotel.index', compact('properties', 'hotelsResponded'));
    }


    public function show(Property $place, $slug): View
    {
        $placeId = $place->id;
        $hotelsResponded = Property::where('place_id', $placeId)->count();
        $properties = Property::where('place_id', $placeId)->get();

        return view('portal.hotel.index', compact('properties', 'hotelsResponded'));
    }
}
