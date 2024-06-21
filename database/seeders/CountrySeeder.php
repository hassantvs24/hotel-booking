<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $countries = config('countries.lists');

        $countryList = [];

        foreach ($countries as $country) {
            $countryList[] = [
                ...$country,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Country::query()->insert($countryList);
    }
}
