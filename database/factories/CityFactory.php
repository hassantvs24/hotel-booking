<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'name'      => $this->faker->city,
            'zip_code'  => $this->faker->postcode,
            'state_id' => $this->faker->randomElement(State::all())->id,
            'lat'       => $this->faker->latitude,
            'long'      => $this->faker->longitude,
        ];
    }
}
