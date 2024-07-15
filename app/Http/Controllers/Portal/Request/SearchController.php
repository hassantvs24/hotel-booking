<?php

namespace App\Http\Controllers\Portal\Request;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'location' => 'required|string|max:255',
        ]);

        $properties = Property::query();

        $location = $request->input('location');
        if ($location) {
            $properties->whereHas('place', function ($q) use ($location) {
                $q->where('name', 'like', '%' . $location . '%')
                    ->orWhereHas('city', function ($q) use ($location) {
                        $q->where('name', 'like', '%' . $location . '%')
                            ->orWhereHas('state', function ($q) use ($location) {
                                $q->where('name', 'like', '%' . $location . '%')
                                    ->orWhereHas('country', function ($q) use ($location) {
                                        $q->where('name', 'like', '%' . $location . '%');
                                    });
                            });
                    });
            });
        }
        $properties = $properties->where('status', Property::STATUS_PUBLISHED)->get();

        return view('portal.properties.propertylist', [
            'properties' => $properties,
            'location' => $location,
            'check_in' => $request->input('check_in'),
            'check_out' => $request->input('check_out'),
            'number_adults' => $request->input('number_adults'),
            'number_children' => $request->input('number_children'),
            'number_rooms' => $request->input('number_rooms'),
        ]);
    }
}
