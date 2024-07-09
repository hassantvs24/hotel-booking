<?php

namespace Database\Factories;

use App\Models\PropertyRule;
use App\Models\PropertyRulesSetup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class PropertyRuleSetupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            'rule_description' => $this->faker->sentence,
            'is_active'        => $this->faker->boolean,
            'property_rule_id' => $this->faker->randomElement(PropertyRule::all())->id,
        ];
    }
}
