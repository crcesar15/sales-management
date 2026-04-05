<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionsEnum: string
{
    case BRANDS_VIEW = 'brand.view';
    case BRANDS_CREATE = 'brand.create';
    case BRANDS_EDIT = 'brand.edit';
    case BRANDS_DELETE = 'brand.delete';
    case BRANDS_RESTORE = 'brand.restore';

    case CATEGORIES_VIEW = 'category.view';
    case CATEGORIES_CREATE = 'category.create';
    case CATEGORIES_EDIT = 'category.edit';
    case CATEGORIES_DELETE = 'category.delete';
    case CATEGORIES_RESTORE = 'category.restore';

    case MEASUREMENT_UNITS_VIEW = 'measurement_unit.view';
    case MEASUREMENT_UNITS_CREATE = 'measurement_unit.create';
    case MEASUREMENT_UNITS_EDIT = 'measurement_unit.edit';
    case MEASUREMENT_UNITS_DELETE = 'measurement_unit.delete';
    case MEASUREMENT_UNITS_RESTORE = 'measurement_unit.restore';

    case PRODUCTS_VIEW = 'product.view';
    case PRODUCTS_CREATE = 'product.create';
    case PRODUCTS_EDIT = 'product.edit';
    case PRODUCTS_DELETE = 'product.delete';
    case PRODUCTS_RESTORE = 'product.restore';

    case ROLES_VIEW = 'role.view';
    case ROLES_CREATE = 'role.create';
    case ROLES_EDIT = 'role.edit';
    case ROLES_DELETE = 'role.delete';

    case USERS_VIEW = 'user.view';
    case USERS_CREATE = 'user.create';
    case USERS_EDIT = 'user.edit';
    case USERS_DELETE = 'user.delete';

    case STORE_VIEW = 'store.view';
    case STORE_CREATE = 'store.create';
    case STORE_EDIT = 'store.edit';
    case STORE_DELETE = 'store.delete';
    case STORE_RESTORE = 'store.restore';

    case SETTINGS_MANAGE = 'setting.manage';

    case ACTIVITY_LOGS_VIEW = 'activity_log.view';
}
