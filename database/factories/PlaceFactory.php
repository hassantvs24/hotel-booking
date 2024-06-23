<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Place>
 */
class PlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'name'             => $this->faker->city,
            'lat'              => $this->faker->latitude,
            'long'             => $this->faker->longitude,
            'zip_code'         => $this->faker->postcode,
            'description'      => $this->faker->text,
            'nearest_police'   => $this->faker->city,
            'nearest_hospital' => $this->faker->city,
            'nearest_fire'     => $this->faker->city,
            'city_id'          => $this->faker->randomElement(City::all())->id,
        ];
    }
}
