<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
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
                PermissionsEnum::ROLES_VIEW->value,
                PermissionsEnum::ROLES_CREATE->value,
                PermissionsEnum::ROLES_EDIT->value,
                PermissionsEnum::ROLES_DELETE->value,
            ],
            'users' => [
                PermissionsEnum::USERS_VIEW->value,
                PermissionsEnum::USERS_CREATE->value,
                PermissionsEnum::USERS_EDIT->value,
                PermissionsEnum::USERS_DELETE->value,
            ],
        ];

        /**
         * @var Role $role
         */
        $role = Role::query()->where('name', RolesEnum::ADMIN->value)->first();

        foreach ($categories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::create(['name' => $permission, 'category' => $category]);
            }

            $role->givePermissionTo($permissions);
        }
    }
}
