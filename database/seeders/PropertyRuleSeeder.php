<?php

namespace Database\Seeders;

use App\Models\PropertyRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertyRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [ 'value' => "no-smoking", 'label' => "No Smoking", 'note' => "Smoking is strictly prohibited inside the property." ],
            [ 'value' => "no-pets", 'label' => "No Pets Allowed", 'note' => "Pets are not allowed to ensure cleanliness and prevent allergies." ],
            [ 'value' => "no-parties", 'label' => "No Parties/Events", 'note' => "Hosting parties or events is not permitted to maintain a peaceful environment." ],
            [ 'value' => "quiet-hours", 'label' => "Quiet Hours (10 PM - 7 AM)", 'note' => "Please respect the quiet hours to avoid disturbing neighbors." ],
            [ 'value' => "no-loud-music", 'label' => "No Loud Music", 'note' => "Playing loud music is not allowed to maintain a calm atmosphere." ],
            [ 'value' => "no-unregistered-guests", 'label' => "No Unregistered Guests", 'note' => "Only registered guests are allowed on the property." ],
            [ 'value' => "check-in-out", 'label' => "Strict Check-In/Check-Out Times", 'note' => "Please adhere to the check-in and check-out times." ],
            [ 'value' => "no-cooking-in-rooms", 'label' => "No Cooking in Rooms", 'note' => "Cooking inside rooms is not allowed for safety and cleanliness reasons." ],
            [ 'value' => "no-shoes-indoors", 'label' => "No Shoes Indoors", 'note' => "Please remove your shoes indoors to maintain hygiene." ],
            [ 'value' => "no-illegal-activities", 'label' => "No Illegal Activities", 'note' => "Engaging in illegal activities is strictly prohibited." ],
            [ 'value' => "no-additional-guests", 'label' => "No Additional Guests", 'note' => "Bringing extra guests beyond the allowed number is not permitted." ],
            [ 'value' => "no-candles", 'label' => "No Candles or Open Flames", 'note' => "Using candles or open flames is not allowed for fire safety." ],
            [ 'value' => "no-damages", 'label' => "No Damages", 'note' => "Please take care of the property and avoid any damages." ],
            [ 'value' => "no-commercial-use", 'label' => "No Commercial Use", 'note' => "The property cannot be used for commercial activities." ],
            [ 'value' => "no-outside-furniture", 'label' => "No Outside Furniture", 'note' => "Do not bring or rearrange outside furniture within the property." ],
            [ 'value' => "no-vaping", 'label' => "No Vaping", 'note' => "Vaping is not allowed inside the property." ],
            [ 'value' => "no-fireworks", 'label' => "No Fireworks", 'note' => "Fireworks are strictly prohibited for safety reasons." ],
            [ 'value' => "no-unauthorized-modifications", 'label' => "No Unauthorized Modifications", 'note' => "Do not make any modifications to the property without approval." ],
            [ 'value' => "no-littering", 'label' => "No Littering", 'note' => "Please dispose of waste properly and keep the property clean." ],
            [ 'value' => "no-unattended-children", 'label' => "No Unattended Children", 'note' => "Children must be supervised at all times for their safety." ],
            [ 'value' => "no-weapons", 'label' => "No Weapons", 'note' => "Bringing weapons onto the property is strictly prohibited." ],
            [ 'value' => "no-drugs", 'label' => "No Drugs", 'note' => "The possession or use of illegal drugs is not allowed." ],
            [ 'value' => "no-excessive-noise", 'label' => "No Excessive Noise", 'note' => "Please keep noise levels to a minimum to respect neighbors." ],
            [ 'value' => "no-overnight-guests", 'label' => "No Overnight Guests", 'note' => "Only registered guests are allowed to stay overnight." ],
            [ 'value' => "no-unauthorized-parking", 'label' => "No Unauthorized Parking", 'note' => "Only authorized vehicles are allowed to park on the premises." ],
            [ 'value' => "no-unauthorized-access", 'label' => "No Unauthorized Access", 'note' => "Do not enter restricted areas without permission." ],
            [ 'value' => "no-unauthorized-subletting", 'label' => "No Unauthorized Subletting", 'note' => "Subletting the property to others is not allowed." ],
            [ 'value' => "no-unattended-pets", 'label' => "No Unattended Pets", 'note' => "Pets must not be left unattended on the property." ],
            [ 'value' => "no-hazardous-materials", 'label' => "No Hazardous Materials", 'note' => "Storing or using hazardous materials is not permitted." ],
            [ 'value' => "respect-neighbors", 'label' => "Respect Neighbors", 'note' => "Please be considerate and respectful of the neighbors." ],
        ];

        foreach ($rules as $rule) {
            PropertyRule::create([
                'rule_title' => $rule['label'],
                'rule_note'  => $rule['note'],
            ]);
        }
    }
}
