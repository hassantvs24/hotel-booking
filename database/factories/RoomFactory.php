<?php

namespace Database\Factories;

use App\Models\BedType;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Room::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */


    public function definition(): array
    {
        return [
            'name'           => $this->faker->word,
            'room_number'    => $this->faker->numberBetween(1, 1000),
            'room_size'      => $this->faker->numberBetween(20, 100),
            'guest_capacity' => $this->faker->numberBetween(1, 5),
            'extra_bed'      => $this->faker->boolean,
            'total_balcony'  => $this->faker->numberBetween(0, 5),
            'total_window'   => $this->faker->numberBetween(0, 10),
            'base_price'     => $this->faker->randomFloat(2, 50, 500),
            'notes'          => $this->faker->sentence,
            'bed_type_id'    => $this->faker->randomElement(BedType::all())->id,
            'room_type_id'   => $this->faker->randomElement(RoomType::all())->id,
            'property_id'    => $this->faker->randomElement(Property::all())->id,
        ];
    }
}
