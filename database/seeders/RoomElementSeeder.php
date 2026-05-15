<?php

namespace Database\Seeders;

use App\Models\BedType;
use App\Models\PriceType;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomElementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        RoomType::factory()->count(50)->create();
        BedType::factory()->count(50)->create();
        PriceType::factory()->count(50)->create();
    }

    /*
     * Creating Room Types
     */
    private function creatingRoomTypes() : void
    {
        $roomTypes = [
            'Standard Room',
            'Deluxe Room',
            'Superior Room',
            'Suite',
            'Junior Suite',
            'Executive Suite',
            'Presidential Suite',
            'Family Room',
            'Studio',
            'Penthouse',
        ];

        foreach ($roomTypes as $type) {
            RoomType::firstOrCreate(['name' => $type]);
        }
    }

    /*
     * Creating Bed Types
     */
    private function creatingBedTypes() : void
    {
        $bedTypes = [
            [
                'name' => 'Single Bed',
                'capacity' => 1,
                'total_bed' => 1,
                'bed_size' => 90
            ],
            [
                'name' => 'Double Bed',
                'capacity' => 2,
                'total_bed' => 1,
                'bed_size' => 140
            ],
            [
                'name' => 'Queen Bed',
                'capacity' => 2,
                'total_bed' => 1,
                'bed_size' => 160
            ],
            [
                'name' => 'King Bed',
                'capacity' => 2,
                'total_bed' => 1,
                'bed_size' => 180
            ],
            [
                'name' => 'Twin Beds',
                'capacity' => 2,
                'total_bed' => 2,
                'bed_size' => 90
            ],
            [
                'name' => 'Bunk Bed',
                'capacity' => 2,
                'total_bed' => 2,
                'bed_size' => 80
            ],
            [
                'name' => 'Sofa Bed',
                'capacity' => 1,
                'total_bed' => 1,
                'bed_size' => 120
            ],
        ];

        foreach ($bedTypes as $bt) {
            BedType::firstOrCreate(['name' => $bt['name']], $bt);
        }
    }

    /*
     * Creating Price Types
     */
    private function creatingPriceTypes() : void
    {

    }
}
