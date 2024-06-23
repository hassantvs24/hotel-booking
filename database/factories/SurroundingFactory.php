<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Surrounding>
 */
class SurroundingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'name'  => $this->faker->word,
            'notes' => $this->faker->text,
        ];
    }

    /**
     * Indicate that the surrounding is active.
     *
     * @return \Database\Factories\SurroundingFactory
     */

    public function configure() :static
    {
        return $this->afterCreating(function (\App\Models\Surrounding $surrounding) {
            $surrounding->places()->saveMany(\App\Models\SurroundingPlace::factory(3)->make());
        });
    }
}
