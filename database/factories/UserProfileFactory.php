<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'address'       => $this->faker->address,
            'city'          => $this->faker->city,
            'state'         => $this->faker->streetName,
            'zip_code'      => $this->faker->postcode,
            'country'       => $this->faker->country,
            'date_of_birth' => $this->faker->date(),
            'bio'           => $this->faker->text,
            'passport'      => rand(100000, 999999),
            'nid'           => rand(100000, 999999),
            'documents'     => $this->faker->text,
        ];
    }
}
