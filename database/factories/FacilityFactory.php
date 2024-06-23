<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\FacilitySub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'name'          => $this->faker->word,
            'notes'         => $this->faker->sentence,
            'facility_type' => $this->faker->randomElement(['hospital', 'clinic', 'nursing home']),
        ];
    }

    public function configure() : static
    {
        return $this->afterCreating(function (Facility $facility) {
            $facility->subFacilities()->saveMany(FacilitySub::factory()->count(3)->make());
        });
    }
}
