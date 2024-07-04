<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewCategory>
 */
class ReviewCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'name'  => $this->faker->word(),
            'notes' => $this->faker->text(),
        ];
    }

    public function configure() : static
    {
        return $this->afterCreating(function (\App\Models\ReviewCategory $reviewCategory) {
            $reviewCategory->reviews()->saveMany(\App\Models\Review::factory()->count(3)->make());
        });
    }
}
