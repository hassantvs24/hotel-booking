<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\FacilitySub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    private static array $facilities = [
        [ 'value' => "wifi", 'name' => "Wi-Fi", 'notes' => "High-speed internet available.", 'facility_type' => "amenity" ],
        [ 'value' => "parking", 'name' => "Parking", 'notes' => "Dedicated parking space available.", 'facility_type' => "feature" ],
        [ 'value' => "security", 'name' => "Security", 'notes' => "24/7 security surveillance.", 'facility_type' => "service" ],
        [ 'value' => "pet-friendly", 'name' => "Pet-Friendly", 'notes' => "Pets allowed on the property.", 'facility_type' => "policy" ],
    ];
    
    public function definition(): array
    {
        $facility = $this->faker->randomElement(self::$facilities);
    
        return [
            'name'          => $facility['name'],
            'notes'         => $facility['notes'],
            'facility_type' => $facility['facility_type'],
        ];
    }
    

    public function configure() : static
    {
        return $this->afterCreating(function (Facility $facility) {
            $facility->subFacilities()->saveMany(FacilitySub::factory()->count(3)->make());
        });
    }
}
