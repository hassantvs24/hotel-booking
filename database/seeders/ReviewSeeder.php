<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\ReviewCategory;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        ReviewCategory::factory()->count(20)->create();
    }
}
