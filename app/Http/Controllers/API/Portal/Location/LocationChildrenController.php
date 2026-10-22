<?php

namespace App\Http\Controllers\API\Portal\Location;

use App\Http\Controllers\BaseController;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use App\Services\Location\AreaPlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What is under a country, a division or a district, from the world list, for the owner's property form.
 * Asked one step at a time (the owner picks a country, then its divisions are fetched ...) so the form never loads the whole world.
 */
class LocationChildrenController extends BaseController
{
    /** GET /portal/location/children?level=states&parent_id=19 — level: states (of a country), cities (of a division), areas (of a district) */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'level'     => 'required|in:states,cities,areas',
            'parent_id' => 'required|integer|min:1',
        ]);

        $parent = (int) $data['parent_id'];

        $rows = match ($data['level']) {
            'states' => State::where('country_id', $parent)->orderBy('name')->get(['id', 'name', 'native_name', 'type', 'country_id']),
            'cities' => City::without('photo')->where('state_id', $parent)->orderBy('name')->get(['id', 'name', 'native_name', 'type', 'state_id']),
            'areas'  => Area::where('city_id', $parent)->orderBy('name')->get(['id', 'name', 'native_name', 'type', 'city_id']),
        };

        $out = ['items' => $rows->values()];

        // under a district the hotel spots set there (Gulshan, Banani ...) are offered too
        if ($data['level'] === 'areas') {
            // (not the places made from an upazila: those are listed as the upazila itself)
            $out['places'] = Place::where('city_id', $parent)
                ->where(fn ($q) => $q->whereNull('external_source')->orWhere('external_source', '!=', AreaPlace::SOURCE))
                ->orderBy('name')->get(['id', 'name', 'city_id']);
        }

        return $this->sendSuccess($out);
    }
}
