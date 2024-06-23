<?php

namespace Database\Factories;

use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SurroundingPlace>
 */
class SurroundingPlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'name'     => $this->faker->word,
            'notes'    => $this->faker->text,
            'lat'      => $this->faker->latitude,
            'long'     => $this->faker->longitude,
            'place_id' => $this->faker->randomElement(Place::all())->id,
        ];
    }
}
