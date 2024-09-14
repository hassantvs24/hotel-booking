<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = config('site_permissions.PERMISSION_LIST');

        foreach ($permissions as $permissionGroup) {
            foreach ($permissionGroup as $permission) {
                Permission::create([
                    'name' => $permission['name'],
                    'slug' => $permission['slug'],
                    'subject' => Str::ucfirst($permission['subject']),
                    'permission_type' => $permission['permission_type'],
                ]);
            }
        }
    }
}
