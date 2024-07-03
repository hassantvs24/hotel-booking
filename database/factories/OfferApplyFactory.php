<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfferApply>
 */
class OfferApplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'owner_price'    => $this->faker->randomFloat(),
            'customer_price' => $this->faker->randomFloat(),
            'notes'          => $this->faker->text(),
            'user_id'        => $this->faker->randomElement(\App\Models\User::all()->pluck('id')),
        ];
    }
}
