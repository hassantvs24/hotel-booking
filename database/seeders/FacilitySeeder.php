<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\FacilitySub;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $facilities = [
            [
                'name'          => 'Internet',
                'facility_type' => 'amenity',
                'facility_for'  => 'Property',
                'subs'          => ['Free Wi-Fi', 'High-Speed Internet', 'Business Center'],
            ],
            [
                'name'          => 'Parking',
                'facility_type' => 'feature',
                'facility_for'  => 'Property',
                'subs'          => ['Free Parking', 'Valet Parking', 'Covered Parking'],
            ],
            [
                'name'          => 'Pool & Spa',
                'facility_type' => 'amenity',
                'facility_for'  => 'Property',
                'subs'          => ['Swimming Pool', 'Spa', 'Hot Tub', 'Sauna'],
            ],
            [
                'name'          => 'Dining',
                'facility_type' => 'service',
                'facility_for'  => 'Property',
                'subs'          => ['Restaurant', 'Bar', 'Room Service', 'Breakfast Included'],
            ],
            [
                'name'          => 'Fitness',
                'facility_type' => 'amenity',
                'facility_for'  => 'Property',
                'subs'          => ['Gym', 'Yoga Room', 'Jogging Track'],
            ],
            [
                'name'          => 'Security',
                'facility_type' => 'service',
                'facility_for'  => 'Property',
                'subs'          => ['24/7 Security', 'CCTV', 'Safe Deposit Box'],
            ],
            [
                'name'          => 'Transport',
                'facility_type' => 'service',
                'facility_for'  => 'Property',
                'subs'          => ['Airport Transfer', 'Car Rental', 'Shuttle Service'],
            ],
            [
                'name'          => 'Room Amenities',
                'facility_type' => 'amenity',
                'facility_for'  => 'Room',
                'subs'          => ['Air Conditioning', 'Flat Screen TV', 'Mini Bar', 'Balcony', 'Sea View'],
            ],
            [
                'name'          => 'Bathroom',
                'facility_type' => 'amenity',
                'facility_for'  => 'Room',
                'subs'          => ['Private Bathroom', 'Bathtub', 'Hot Shower', 'Hairdryer'],
            ],
            [
                'name'          => 'Kitchen',
                'facility_type' => 'amenity',
                'facility_for'  => 'Room',
                'subs'          => ['Kitchenette', 'Microwave', 'Refrigerator', 'Coffee Maker'],
            ],
        ];

        foreach ($facilities as $f) {
            $facility = Facility::firstOrCreate(
                ['name' => $f['name']],
                [
                    'name'          => $f['name'],
                    'facility_type' => $f['facility_type'],
                    'facility_for'  => $f['facility_for'],
                ]
            );

            foreach ($f['subs'] as $sub) {
                FacilitySub::firstOrCreate(
                    ['name' => $sub, 'facility_id' => $facility->id],
                    ['name' => $sub, 'facility_id' => $facility->id]
                );
            }
        }
    }
}
