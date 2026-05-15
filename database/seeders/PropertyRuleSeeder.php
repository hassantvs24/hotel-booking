<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyRule;
use App\Models\PropertyRulesSetup;
use Illuminate\Database\Seeder;

class PropertyRuleSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Seed master rules ───────────────────────────
        $this->seedMasterRules();

        // ── 2. Attach rules to each published property ─────
        $this->attachRulesToProperties();
    }

    // ══════════════════════════════════════════════════════
    //  MASTER RULES
    //  Uses firstOrCreate — safe to re-run
    // ══════════════════════════════════════════════════════

    private function seedMasterRules(): void
    {
        $rules = [
            ['rule_title' => 'No Smoking',                   'rule_note' => 'Smoking is strictly prohibited inside the property.'],
            ['rule_title' => 'No Pets Allowed',              'rule_note' => 'Pets are not allowed to ensure cleanliness and prevent allergies.'],
            ['rule_title' => 'No Parties/Events',            'rule_note' => 'Hosting parties or events is not permitted to maintain a peaceful environment.'],
            ['rule_title' => 'Quiet Hours (10 PM - 7 AM)',   'rule_note' => 'Please respect the quiet hours to avoid disturbing neighbors.'],
            ['rule_title' => 'No Loud Music',                'rule_note' => 'Playing loud music is not allowed to maintain a calm atmosphere.'],
            ['rule_title' => 'No Unregistered Guests',       'rule_note' => 'Only registered guests are allowed on the property.'],
            ['rule_title' => 'Strict Check-In/Check-Out',    'rule_note' => 'Please adhere to the check-in and check-out times.'],
            ['rule_title' => 'No Cooking in Rooms',          'rule_note' => 'Cooking inside rooms is not allowed for safety and cleanliness reasons.'],
            ['rule_title' => 'No Shoes Indoors',             'rule_note' => 'Please remove your shoes indoors to maintain hygiene.'],
            ['rule_title' => 'No Illegal Activities',        'rule_note' => 'Engaging in illegal activities is strictly prohibited.'],
            ['rule_title' => 'No Additional Guests',         'rule_note' => 'Bringing extra guests beyond the allowed number is not permitted.'],
            ['rule_title' => 'No Candles or Open Flames',    'rule_note' => 'Using candles or open flames is not allowed for fire safety.'],
            ['rule_title' => 'No Damages',                   'rule_note' => 'Please take care of the property and avoid any damages.'],
            ['rule_title' => 'No Commercial Use',            'rule_note' => 'The property cannot be used for commercial activities.'],
            ['rule_title' => 'No Vaping',                    'rule_note' => 'Vaping is not allowed inside the property.'],
            ['rule_title' => 'No Fireworks',                 'rule_note' => 'Fireworks are strictly prohibited for safety reasons.'],
            ['rule_title' => 'No Unauthorized Modifications','rule_note' => 'Do not make any modifications to the property without approval.'],
            ['rule_title' => 'No Littering',                 'rule_note' => 'Please dispose of waste properly and keep the property clean.'],
            ['rule_title' => 'No Unattended Children',       'rule_note' => 'Children must be supervised at all times for their safety.'],
            ['rule_title' => 'No Weapons',                   'rule_note' => 'Bringing weapons onto the property is strictly prohibited.'],
            ['rule_title' => 'No Drugs',                     'rule_note' => 'The possession or use of illegal drugs is not allowed.'],
            ['rule_title' => 'No Excessive Noise',           'rule_note' => 'Please keep noise levels to a minimum to respect neighbors.'],
            ['rule_title' => 'No Overnight Guests',          'rule_note' => 'Only registered guests are allowed to stay overnight.'],
            ['rule_title' => 'No Unauthorized Parking',      'rule_note' => 'Only authorized vehicles are allowed to park on the premises.'],
            ['rule_title' => 'No Unauthorized Access',       'rule_note' => 'Do not enter restricted areas without permission.'],
            ['rule_title' => 'No Unauthorized Subletting',   'rule_note' => 'Subletting the property to others is not allowed.'],
            ['rule_title' => 'No Hazardous Materials',       'rule_note' => 'Storing or using hazardous materials is not permitted.'],
            ['rule_title' => 'Respect Neighbors',            'rule_note' => 'Please be considerate and respectful of the neighbors.'],
            ['rule_title' => 'No Outside Furniture',         'rule_note' => 'Do not bring or rearrange outside furniture within the property.'],
            ['rule_title' => 'No Unattended Pets',           'rule_note' => 'Pets must not be left unattended on the property.'],
        ];

        foreach ($rules as $rule) {
            PropertyRule::firstOrCreate(
                ['rule_title' => $rule['rule_title']],
                $rule
            );
        }

        $this->command->info('✓ ' . count($rules) . ' property rules seeded.');
    }

    // ══════════════════════════════════════════════════════
    //  ATTACH RULES TO PROPERTIES
    //  Each property gets 5–8 randomly selected rules
    //  stored in property_rules_setups
    // ══════════════════════════════════════════════════════

    private function attachRulesToProperties(): void
    {
        // Check if PropertyRulesSetup model/table exists
        if (!class_exists(PropertyRulesSetup::class)) {
            $this->command->warn('PropertyRulesSetup model not found — skipping rule attachment.');
            return;
        }

        $allRules   = PropertyRule::all();
        $properties = Property::all();

        if ($allRules->isEmpty() || $properties->isEmpty()) return;

        // Core rules every property gets
        $coreRuleTitles = [
            'No Smoking',
            'No Illegal Activities',
            'Strict Check-In/Check-Out',
            'Respect Neighbors',
        ];
        $coreRules = $allRules->whereIn('rule_title', $coreRuleTitles);

        foreach ($properties as $property) {
            // Skip if rules already attached
            if (PropertyRulesSetup::where('property_id', $property->id)->exists()) {
                continue;
            }

            // Core rules + 3–5 random additional rules
            $additionalRules = $allRules
                ->whereNotIn('rule_title', $coreRuleTitles)
                ->random(min(rand(3, 5), $allRules->count()));

            $rulesToAttach = $coreRules->merge($additionalRules);

            foreach ($rulesToAttach as $rule) {
                PropertyRulesSetup::firstOrCreate(
                    [
                        'property_id'      => $property->id,
                        'property_rule_id' => $rule->id,
                    ],
                    [
                        'property_id'      => $property->id,
                        'property_rule_id' => $rule->id,
                        'rule_description' => $rule->rule_note,
                        'is_active'        => 1,
                    ]
                );
            }
        }

        $this->command->info('✓ Rules attached to ' . $properties->count() . ' properties.');
    }
}
