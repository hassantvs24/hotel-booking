<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
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
        $permissions = Permission::query()
            ->where('permission_type', 'property')
            ->get()
            ->pluck('id', 'id');
        $propertyPermissions = Permission::query()
            ->where('permission_type', 'property')
            ->get()
            ->pluck('id', 'id');

        $properties = Property::query()->with('user')->get();

        $admin = User::query()->where('email', 'admin@gmail.com')->first();

        $adminRole = Role::create([
            'name'        => 'Super Admin',
            'description' => 'Main Administrator',
        ]);

        $propertyOwnerRole = Role::create([
            'name'        => 'Vendor',
            'description' => 'Property Owner',
        ]);

        Role::create([
            'name'        => 'User',
            'description' => 'Default User Role',
        ]);

        $adminRole->syncPermissions($permissions);
        $propertyOwnerRole->syncPermissions($propertyPermissions);

        $admin->assignRole([$adminRole->id]);

        foreach ($properties as $property) {
            $role = Role::create([
                'name' => 'Property Manager for ' . $property->name,
                'description' => 'Property Manager for ' . $property->name,
                'property_id' => $property->id,
            ]);

            $role->syncPermissions($propertyPermissions);
        }
    }
}
