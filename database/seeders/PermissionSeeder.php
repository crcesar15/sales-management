<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'roles' => [
                'roles-view',
                'roles-create',
                'roles-edit',
                'roles-delete',
            ],
            'users' => [
                'users-view',
                'users-create',
                'users-edit',
                'users-delete',
            ],
        ];

        $role = Role::find(1);

        foreach ($categories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::create(['name' => $permission, 'category' => $category]);
            }

            $role->givePermissionTo($permissions);
        }
    }
}
