<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;
use Illuminate\Database\Seeder;

class AllPlaceSeeder extends Seeder
{
    public function run(): void
    {
        $this->createCountries();
        $this->createStates();
        $this->createCities();
        $this->createPlaces();
    }

    // ── Countries ──────────────────────────────────────────
    private function createCountries(): void
    {
        $countries = [
            ['name' => 'Bangladesh', 'short_name' => 'BD', 'phone_code' => '+880'],
            ['name' => 'India',      'short_name' => 'IN', 'phone_code' => '+91'],
            ['name' => 'Maldives',   'short_name' => 'MV', 'phone_code' => '+960'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(['short_name' => $country['short_name']], $country);
        }
    }

    // ── States ─────────────────────────────────────────────
    private function createStates(): void
    {
        $bd = Country::where('short_name', 'BD')->first();
        $in = Country::where('short_name', 'IN')->first();
        $mv = Country::where('short_name', 'MV')->first();

        $states = [
            ['name' => 'Dhaka Division',      'country_id' => $bd->id],
            ['name' => 'Chittagong Division', 'country_id' => $bd->id],
            ['name' => 'Sylhet Division',     'country_id' => $bd->id],
            ['name' => "Cox's Bazar",         'country_id' => $bd->id],  // straight apostrophe
            ['name' => 'West Bengal',         'country_id' => $in->id],
            ['name' => 'Goa',                 'country_id' => $in->id],
            ['name' => 'North Male Atoll',    'country_id' => $mv->id],  // avoid special char
        ];

        foreach ($states as $state) {
            State::firstOrCreate(
                ['name' => $state['name'], 'country_id' => $state['country_id']],
                $state
            );
        }
    }

    // ── Cities ─────────────────────────────────────────────
    private function createCities(): void
    {
        $cities = [
            ['name' => 'Dhaka',           'state' => 'Dhaka Division',      'lat' => 23.8103, 'long' => 90.4125, 'zip_code' => '1000'],
            ['name' => 'Narayanganj',     'state' => 'Dhaka Division',      'lat' => 23.6238, 'long' => 90.5000, 'zip_code' => '1400'],
            ['name' => 'Gazipur',         'state' => 'Dhaka Division',      'lat' => 23.9999, 'long' => 90.4203, 'zip_code' => '1700'],
            ['name' => 'Chittagong',      'state' => 'Chittagong Division', 'lat' => 22.3569, 'long' => 91.7832, 'zip_code' => '4000'],
            ['name' => "Cox's Bazar",     'state' => 'Chittagong Division', 'lat' => 21.4272, 'long' => 92.0058, 'zip_code' => '4700'],
            ['name' => 'Rangamati',       'state' => 'Chittagong Division', 'lat' => 22.6500, 'long' => 92.2000, 'zip_code' => '4500'],
            ['name' => 'Sylhet',          'state' => 'Sylhet Division',     'lat' => 24.8949, 'long' => 91.8687, 'zip_code' => '3100'],
            ['name' => 'Sunamganj',       'state' => 'Sylhet Division',     'lat' => 25.0658, 'long' => 91.3950, 'zip_code' => '3000'],
            ['name' => "Cox's Bazar City",'state' => "Cox's Bazar",         'lat' => 21.4272, 'long' => 92.0058, 'zip_code' => '4700'],
            ['name' => 'Teknaf',          'state' => "Cox's Bazar",         'lat' => 20.8655, 'long' => 92.3025, 'zip_code' => '4761'],
            ['name' => 'Kolkata',         'state' => 'West Bengal',         'lat' => 22.5726, 'long' => 88.3639, 'zip_code' => '700001'],
            ['name' => 'Panaji',          'state' => 'Goa',                 'lat' => 15.4909, 'long' => 73.8278, 'zip_code' => '403001'],
            ['name' => 'Male',            'state' => 'North Male Atoll',    'lat' => 4.1755,  'long' => 73.5093, 'zip_code' => '20000'],
        ];

        foreach ($cities as $city) {
            $state = State::where('name', $city['state'])->first();
            if (!$state) continue;

            City::firstOrCreate(
                ['name' => $city['name'], 'state_id' => $state->id],
                [
                    'name'     => $city['name'],
                    'state_id' => $state->id,
                    'lat'      => $city['lat'],
                    'long'     => $city['long'],
                    'zip_code' => $city['zip_code'],
                ]
            );
        }
    }

    // ── Places ─────────────────────────────────────────────
    private function createPlaces(): void
    {
        $places = [
            ['name' => 'Gulshan',       'city' => 'Dhaka',            'lat' => 23.7806, 'long' => 90.4193, 'full_address' => 'Gulshan, Dhaka North City Corporation, Dhaka, Bangladesh'],
            ['name' => 'Banani',        'city' => 'Dhaka',            'lat' => 23.7937, 'long' => 90.4066, 'full_address' => 'Banani, Dhaka North City Corporation, Dhaka, Bangladesh'],
            ['name' => 'Dhanmondi',     'city' => 'Dhaka',            'lat' => 23.7461, 'long' => 90.3742, 'full_address' => 'Dhanmondi, Dhaka South City Corporation, Dhaka, Bangladesh'],
            ['name' => 'Uttara',        'city' => 'Dhaka',            'lat' => 23.8759, 'long' => 90.3795, 'full_address' => 'Uttara, Dhaka North City Corporation, Dhaka, Bangladesh'],
            ['name' => 'Motijheel',     'city' => 'Dhaka',            'lat' => 23.7337, 'long' => 90.4176, 'full_address' => 'Motijheel, Dhaka South City Corporation, Dhaka, Bangladesh'],
            ['name' => 'Agrabad',       'city' => 'Chittagong',       'lat' => 22.3300, 'long' => 91.8100, 'full_address' => 'Agrabad, Chittagong City Corporation, Chittagong, Bangladesh'],
            ['name' => 'Nasirabad',     'city' => 'Chittagong',       'lat' => 22.3700, 'long' => 91.7900, 'full_address' => 'Nasirabad, Chittagong City Corporation, Chittagong, Bangladesh'],
            ['name' => 'GEC Circle',    'city' => 'Chittagong',       'lat' => 22.3569, 'long' => 91.8200, 'full_address' => 'GEC Circle, Khulshi, Chittagong, Bangladesh'],
            ['name' => 'Cox Beach Area','city' => "Cox's Bazar City", 'lat' => 21.4272, 'long' => 92.0058, 'full_address' => "Cox's Bazar Beach Road, Cox's Bazar, Chittagong Division, Bangladesh"],
            ['name' => 'Inani Beach',   'city' => "Cox's Bazar City", 'lat' => 21.2810, 'long' => 92.0336, 'full_address' => "Inani Beach, Ukhia, Cox's Bazar, Bangladesh"],
            ['name' => 'Laboni Point',  'city' => "Cox's Bazar City", 'lat' => 21.4308, 'long' => 92.0069, 'full_address' => "Laboni Point, Cox's Bazar City, Bangladesh"],
            ['name' => 'Himchhari',     'city' => "Cox's Bazar City", 'lat' => 21.3641, 'long' => 92.0164, 'full_address' => "Himchhari, Cox's Bazar, Chittagong Division, Bangladesh"],
            ['name' => 'Zindabazar',    'city' => 'Sylhet',           'lat' => 24.8957, 'long' => 91.8697, 'full_address' => 'Zindabazar, Sylhet City Corporation, Sylhet, Bangladesh'],
            ['name' => 'Ambarkhana',    'city' => 'Sylhet',           'lat' => 24.9001, 'long' => 91.8600, 'full_address' => 'Ambarkhana, Sylhet City Corporation, Sylhet, Bangladesh'],
            ['name' => 'Rangamati Town','city' => 'Rangamati',        'lat' => 22.6500, 'long' => 92.1800, 'full_address' => 'Rangamati Town, Rangamati Hill District, Chittagong Division, Bangladesh'],
            ['name' => 'Saint Martin',  'city' => 'Teknaf',           'lat' => 20.6264, 'long' => 92.3226, 'full_address' => "Saint Martin Island, Teknaf, Cox's Bazar, Bangladesh"],
            ['name' => 'Park Street',   'city' => 'Kolkata',          'lat' => 22.5535, 'long' => 88.3514, 'full_address' => 'Park Street Area, Kolkata, West Bengal, India'],
            ['name' => 'Calangute',     'city' => 'Panaji',           'lat' => 15.5440, 'long' => 73.7552, 'full_address' => 'Calangute Beach, North Goa, Goa, India'],
            ['name' => 'Hulhumale',     'city' => 'Male',             'lat' => 4.2121,  'long' => 73.5407, 'full_address' => 'Hulhumale, North Male Atoll, Maldives'],
            ['name' => 'Maafushi',      'city' => 'Male',             'lat' => 3.9333,  'long' => 73.4833, 'full_address' => 'Maafushi Island, Kaafu Atoll, Maldives'],
        ];

        foreach ($places as $p) {
            $city = City::where('name', $p['city'])->first();
            if (!$city) continue;

            Place::firstOrCreate(
                ['name' => $p['name'], 'city_id' => $city->id],
                [
                    'city_id'      => $city->id,
                    'name'         => $p['name'],
                    'lat'          => $p['lat'],
                    'long'         => $p['long'],
                    'full_address' => $p['full_address'],
                ]
            );
        }
    }
}
