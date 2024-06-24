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
        PropertyRule::factory()->count(100)->create();
    }
}
