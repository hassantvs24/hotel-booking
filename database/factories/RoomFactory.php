<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BedType;
use App\Models\RoomType;
use App\Models\Property;
use App\Models\Room;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{

    protected $model = Room::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'photo' => $this->faker->imageUrl(),
            'room_number' => $this->faker->numberBetween(1, 1000),
            'room_size' => $this->faker->numberBetween(20, 100), // Square meter
            'guest_capacity' => $this->faker->numberBetween(1, 5),
            'extra_bed' => $this->faker->boolean,
            'total_balcony' => $this->faker->numberBetween(0, 5),
            'total_window' => $this->faker->numberBetween(0, 10),
            'base_price' => $this->faker->randomFloat(2, 50, 500),
            'notes' => $this->faker->sentence,
            'bed_type_id' => $this->faker->randomElement(BedType::all())->id,
            'room_type_id' => $this->faker->randomElement(RoomType::all())->id,
            'property_id' => $this->faker->randomElement(Property::all())->id,
        ];
    }

    /**
     * Indicate that the surrounding is active.
     *
     * @return \Database\Factories\RoomFactory
     */

    public function configure()
    {
        //
    }
}
