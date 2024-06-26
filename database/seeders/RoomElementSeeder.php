<?php

namespace Database\Seeders;

use App\Models\BedType;
use App\Models\PriceType;
use App\Models\RoomType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomElementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RoomType::factory()->count(50)->create();
        BedType::factory()->count(50)->create();
        PriceType::factory()->count(50)->create();
    }
}
