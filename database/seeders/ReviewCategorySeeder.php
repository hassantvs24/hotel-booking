<?php

namespace Database\Seeders;

use App\Models\ReviewCategory;
use Illuminate\Database\Seeder;

class ReviewCategorySeeder extends Seeder
{
    /**
     * Booking.com-style category set. Idempotent (safe to re-run) and
     * removes the old single "Property Review" placeholder, which no
     * review data references.
     */
    public function run(): void
    {
        ReviewCategory::where('name', 'Property Review')->delete();

        foreach (['Staff', 'Facilities', 'Cleanliness', 'Comfort', 'Value for money', 'Location'] as $name) {
            ReviewCategory::updateOrCreate(['name' => $name]);
        }
    }
}
