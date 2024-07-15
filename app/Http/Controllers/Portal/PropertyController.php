<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(): View
    {
        return view('portal.properties.index');
    }
    public function all_properties(): View
    {
        $hotelsResponded = Property::count();
        $properties = Property::query()
            ->where('status', Property::STATUS_PUBLISHED)
            ->get();

        return view('portal.properties.propertylist', compact('properties', 'hotelsResponded'));
    }
    public function property_Details(Property $property, $slug)
    {
        return view('portal.properties.property-details', compact('property'));
    }

    public function place_property(Place $place, $slug): View
    {
        $properties = Property::query()
            ->where([
                'place_id' => $place->id,
                'status'   => Property::STATUS_PUBLISHED,
            ])->get();
        return view('portal.properties.propertylist', compact('properties'));
    }
}
