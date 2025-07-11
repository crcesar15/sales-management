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
                'role-list',
                'role-view',
                'role-create',
                'role-edit',
                'role-delete',
            ],
            'users' => [
                'user-list',
                'user-view',
                'user-create',
                'user-edit',
                'user-delete',
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
