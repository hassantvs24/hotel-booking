<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run() : void
    {
        $this->call([
            SettingSeeder::class,
            PermissionSeeder::class,
            AllPlaceSeeder::class,
            UserSeeder::class,
            //ACLSeeder::class,
            PropertyCategorySeeder::class,
            FacilitySeeder::class,
            RoomElementSeeder::class,
            PropertySeeder::class,
            PropertyRuleSeeder::class,

            SurroundingSeeder::class,
            RoleSeeder::class,
            //RoomSeeder::class,
            //ReviewSeeder::class,
            //OfferSeeder::class
        ]);
    }
}
