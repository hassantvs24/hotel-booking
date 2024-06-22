<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\State>
 */
class StateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = Country::all();

        return [
            'name' => $this->faker->city,
            'short_name' => $this->faker->citySuffix,
            'countries_id' => $this->faker->randomElement($countries)->id,
        ];
    }
}
