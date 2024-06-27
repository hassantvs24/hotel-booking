<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ACLSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            PlaceSeeder::class,
            FacilitySeeder::class,
            SurroundingSeeder::class,
            PropertyCategorySeeder::class,
            PropertyRuleSeeder::class,
            RoomElementSeeder::class,
            PropertySeeder::class,
            RoomSeeder::class,
        ]);
    }
}
