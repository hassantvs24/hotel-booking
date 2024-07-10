<?php

namespace Database\Seeders;

use Database\Factories\RoomFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RoomFactory::new()->count(20)->create(); //using RoomFactory and Model was defined in Factory
    }
}
