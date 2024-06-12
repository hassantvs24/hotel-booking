<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $permissions = Permission::query()->pluck('id', 'id')->all();

        $superAdminRole = Role::create([
            'name'        => 'Super Admin',
            'description' => 'Main Administrator',
        ]);

        $adminRole = Role::create([
            'name'        => 'Admin',
            'description' => 'Main Administrator',
        ]);

        $superAdminRole->syncPermissions($permissions);
        $adminRole->syncPermissions($permissions);


    }
}
