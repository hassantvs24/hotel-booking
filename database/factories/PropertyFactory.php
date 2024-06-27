<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\PropertyCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'lat' => $this->faker->latitude,
            'long' => $this->faker->longitude,
            'photo' => $this->faker->imageUrl(),
            'address' => $this->faker->address,
            'zip_code' => $this->faker->postcode,
            'total_room' => $this->faker->numberBetween(1, 100),
            'currency' => $this->faker->currencyCode,
            'rating' => $this->faker->randomFloat(2, 0, 5),
            'google_review' => $this->faker->url,
            'seo_title' => $this->faker->sentence,
            'seo_meta' => $this->faker->sentence,
            'property_class' => $this->faker->randomElement(['7 Stars', '6 Stars', '5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star', 'Unrated']),
            'status' => $this->faker->randomElement(['Pending', 'Published', 'Unpublished']),
            'property_category_id' =>  $this->faker->randomElement(PropertyCategory::all())->id, 
            'place_id' =>  $this->faker->randomElement(Place::all())->id, 
            'user_id' =>  $this->faker->randomElement(User::all())->id,
            'deleted_at' => null, 
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
