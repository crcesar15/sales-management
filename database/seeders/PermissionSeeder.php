<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PermissionSeeder extends Seeder
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

        /**
         * @var Role $role
         */
        $role = Role::query()->find(1);

        foreach ($categories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::create(['name' => $permission, 'category' => $category]);
            }

            $role->givePermissionTo($permissions);
        }
    }
}
