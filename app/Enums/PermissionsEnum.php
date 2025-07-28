<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionsEnum: string
{
    case BRANDS_VIEW = 'brands-view';
    case BRANDS_CREATE = 'brands-create';
    case BRANDS_EDIT = 'brands-edit';
    case BRANDS_DELETE = 'brands-delete';

    case CATEGORIES_VIEW = 'categories-view';
    case CATEGORIES_CREATE = 'categories-create';
    case CATEGORIES_EDIT = 'categories-edit';
    case CATEGORIES_DELETE = 'categories-delete';

    case MEASUREMENT_UNITS_VIEW = 'measurement-units-view';
    case MEASUREMENT_UNITS_CREATE = 'measurement-units-create';
    case MEASUREMENT_UNITS_EDIT = 'measurement-units-edit';
    case MEASUREMENT_UNITS_DELETE = 'measurement-units-delete';

    case ROLES_VIEW = 'roles-view';
    case ROLES_CREATE = 'roles-create';
    case ROLES_EDIT = 'roles-edit';
    case ROLES_DELETE = 'roles-delete';

    case USERS_VIEW = 'users-view';
    case USERS_CREATE = 'users-create';
    case USERS_EDIT = 'users-edit';
    case USERS_DELETE = 'users-delete';
}
