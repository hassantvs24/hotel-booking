<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ACLSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $admin = User::query()
            ->where('email', '=', 'admin@gmail.com')
            ->first();

        $superAdmin = User::query()
            ->where('email', '=', 'superadmin@gmail.com')
            ->first();

        $adminRole = Role::query()
            ->where('name', '=', 'Admin')
            ->first();

        $superAdminRole = Role::query()
            ->where('name', '=', 'Super Admin')
            ->first();

        if ($adminRole) {
            $admin?->assignRole([$adminRole->id]);
        }

        if ($superAdminRole) {
            $superAdmin?->assignRole([$adminRole->id]);
        }

    }
}
