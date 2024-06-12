<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $adminData = [
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@gmail.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name'     => 'Admin',
                'email'    => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        User::query()->insert($adminData);

        User::factory(20)->create();
    }
}
