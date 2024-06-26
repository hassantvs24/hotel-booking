<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BedType>
 */
class BedTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'name'      => $this->faker->word,
            'capacity'  => $this->faker->numberBetween(1, 10),
            'total_bed' => $this->faker->numberBetween(1, 10),
            'bed_size'  => $this->faker->numberBetween(1, 10),
        ];
    }
}
