<?php

namespace Database\Seeders;

use App\Models\PropertyCategory;
use Illuminate\Database\Seeder;

class PropertyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $categories = [
            'Hotel', 'Resort', 'Villa', 'Apartment',
            'Guesthouse', 'Boutique Hotel', 'Hostel',
            'Eco Lodge', 'Beach House', 'Bungalow',
        ];

        foreach ($categories as $cat) {
            PropertyCategory::firstOrCreate(['name' => $cat]);
        }
    }
}
