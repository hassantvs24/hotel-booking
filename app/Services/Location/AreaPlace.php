<?php

namespace App\Services\Location;

use App\Models\Area;
use App\Models\Place;
use Illuminate\Validation\ValidationException;

/**
 * A property is found by the place it sits at. When an owner picks an upazila / area from the world list, that area becomes
 * a place of its own (one per area, shared by every property there), so searches by district, division, country or the
 * area's name all find the hotel.
 */
class AreaPlace
{
    public const SOURCE = 'world_list';

    /**
     * @param int|string $cityId the district the owner chose
     * @param ?float $lat the pin the owner put on the map (the area has no point of its own)
     */
    public function forArea(int $areaId, $cityId, ?float $lat, ?float $long): Place
    {
        $area = Area::with('city.state.country')->find($areaId);

        if (!$area || (int) $area->city_id !== (int) $cityId) {
            throw ValidationException::withMessages(['area_id' => ['That upazila / area is not in the district you chose.']]);
        }

        $existing = Place::where('external_source', self::SOURCE)->where('external_id', 'area:' . $area->id)->first();
        if ($existing) {
            return $existing;
        }

        $lat  = $lat  ?? $area->lat  ?? $area->city?->lat;
        $long = $long ?? $area->long ?? $area->city?->long;

        if ($lat === null || $long === null) {
            throw ValidationException::withMessages(['latitude' => ['Put the pin on the map first, so we know where this area is.']]);
        }

        return Place::create([
            'city_id'         => $area->city_id,
            'name'            => $area->name,
            'lat'             => $lat,
            'long'            => $long,
            'external_id'     => 'area:' . $area->id,
            'external_source' => self::SOURCE,
            'full_address'    => collect([$area->name, $area->city?->name, $area->city?->state?->name, $area->city?->state?->country?->name])->filter()->implode(', '),
        ]);
    }
}
