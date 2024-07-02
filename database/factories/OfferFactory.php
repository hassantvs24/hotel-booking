<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'checkin'     => $this->faker->date(),
            'checkout'    => $this->faker->date(),
            'adult'       => $this->faker->randomNumber(),
            'children'    => $this->faker->randomNumber(),
            'room'        => $this->faker->randomNumber(),
            'price'       => $this->faker->randomFloat(),
            'final_price' => $this->faker->randomFloat(),
            'notes'       => $this->faker->text(),
            'offer_from'  => $this->faker->randomElement(['Generale', 'Request']),
            'status'      => $this->faker->randomElement(['Pending', 'Negotiate', 'Canceled', 'Accepted']),
            'room_id'     => $this->faker->randomElement(Room::all()->pluck('id')),
            'user_id'     => $this->faker->randomElement(User::all()->pluck('id')),
        ];
    }

    public function configure() : static
    {
        return $this->afterCreating(function (\App\Models\Offer $offer) {
            $offer->applications()->saveMany(\App\Models\OfferApply::factory()->count(3)->make());
        });
    }
}
