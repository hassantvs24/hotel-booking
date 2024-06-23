<?php

namespace Database\Seeders;

use App\Models\Surrounding;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SurroundingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Surrounding::factory()->count(10)->create();
    }
}
