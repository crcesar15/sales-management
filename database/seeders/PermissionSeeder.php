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
            'brands' => [
                PermissionsEnum::BRANDS_VIEW->value,
                PermissionsEnum::BRANDS_CREATE->value,
                PermissionsEnum::BRANDS_EDIT->value,
                PermissionsEnum::BRANDS_DELETE->value,
            ],
            'categories' => [
                PermissionsEnum::CATEGORIES_VIEW->value,
                PermissionsEnum::CATEGORIES_CREATE->value,
                PermissionsEnum::CATEGORIES_EDIT->value,
                PermissionsEnum::CATEGORIES_DELETE->value,
            ],
            'measurement units' => [
                PermissionsEnum::MEASUREMENT_UNITS_VIEW->value,
                PermissionsEnum::MEASUREMENT_UNITS_CREATE->value,
                PermissionsEnum::MEASUREMENT_UNITS_EDIT->value,
                PermissionsEnum::MEASUREMENT_UNITS_DELETE->value,
            ],
            'products' => [
                PermissionsEnum::PRODUCTS_VIEW->value,
                PermissionsEnum::PRODUCTS_CREATE->value,
                PermissionsEnum::PRODUCTS_EDIT->value,
                PermissionsEnum::PRODUCTS_DELETE->value,
            ],
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

        $role = Role::query()->where('name', RolesEnum::ADMIN->value)->firstOrFail();

        foreach ($categories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::create(['name' => $permission, 'category' => $category]);
            }

            $role->givePermissionTo($permissions);
        }
    }
}
