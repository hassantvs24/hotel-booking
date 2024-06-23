<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Place;
use App\Models\Surrounding;

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
    public function definition(): array
    {
        return [

            'name' => $this->faker->name,
            'lat' => $this->faker->latitude,
            'long' => $this->faker->longitude,
            'notes' => $this->faker->text,
            'photo' => $this->faker->imageUrl(),
            'place_id' => $this->faker->randomElement(Place::all())->id,
            'surrounding_id' => $this->faker->randomElement(Surrounding::all())->id,
        ];
    }
}
