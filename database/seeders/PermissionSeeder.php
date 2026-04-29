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
                PermissionsEnum::BRANDS_RESTORE->value,
            ],
            'categories' => [
                PermissionsEnum::CATEGORIES_VIEW->value,
                PermissionsEnum::CATEGORIES_CREATE->value,
                PermissionsEnum::CATEGORIES_EDIT->value,
                PermissionsEnum::CATEGORIES_DELETE->value,
                PermissionsEnum::CATEGORIES_RESTORE->value,
            ],
            'measurement units' => [
                PermissionsEnum::MEASUREMENT_UNITS_VIEW->value,
                PermissionsEnum::MEASUREMENT_UNITS_CREATE->value,
                PermissionsEnum::MEASUREMENT_UNITS_EDIT->value,
                PermissionsEnum::MEASUREMENT_UNITS_DELETE->value,
                PermissionsEnum::MEASUREMENT_UNITS_RESTORE->value,
            ],
            'products' => [
                PermissionsEnum::PRODUCTS_VIEW->value,
                PermissionsEnum::PRODUCTS_CREATE->value,
                PermissionsEnum::PRODUCTS_EDIT->value,
                PermissionsEnum::PRODUCTS_DELETE->value,
                PermissionsEnum::PRODUCTS_RESTORE->value,
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
            'stores' => [
                PermissionsEnum::STORE_VIEW->value,
                PermissionsEnum::STORE_CREATE->value,
                PermissionsEnum::STORE_EDIT->value,
                PermissionsEnum::STORE_DELETE->value,
                PermissionsEnum::STORE_RESTORE->value,
            ],
            'activity logs' => [
                PermissionsEnum::ACTIVITY_LOGS_VIEW->value,
            ],
            'inventory' => [
                PermissionsEnum::INVENTORY_VIEW->value,
                PermissionsEnum::INVENTORY_EDIT->value,
            ],
            'stock alerts' => [
                PermissionsEnum::STOCK_ALERTS_VIEW->value,
            ],
            'batches' => [
                PermissionsEnum::BATCHES_VIEW->value,
                PermissionsEnum::STOCK_ADJUST->value,
            ],
            'stock transfers' => [
                PermissionsEnum::STOCK_TRANSFERS_VIEW->value,
                PermissionsEnum::STOCK_TRANSFERS_CREATE->value,
                PermissionsEnum::STOCK_TRANSFERS_EDIT->value,
                PermissionsEnum::STOCK_TRANSFERS_CANCEL->value,
            ],
            'settings' => [
                PermissionsEnum::SETTINGS_MANAGE->value,
            ],
        ];

        $role = Role::query()->where('name', RolesEnum::ADMIN->value)->firstOrFail();

        foreach ($categories as $category => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission], ['category' => $category]);
            }

            $role->givePermissionTo($permissions);
        }
    }
}
