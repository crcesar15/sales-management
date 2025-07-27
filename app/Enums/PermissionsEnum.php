<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionsEnum: string
{
    case CATEGORY_VIEW = 'categories-view';
    case CATEGORY_CREATE = 'categories-create';
    case CATEGORY_EDIT = 'categories-edit';
    case CATEGORY_DELETE = 'categories-delete';

    case ROLES_VIEW = 'roles-view';
    case ROLES_CREATE = 'roles-create';
    case ROLES_EDIT = 'roles-edit';
    case ROLES_DELETE = 'roles-delete';

    case USERS_VIEW = 'users-view';
    case USERS_CREATE = 'users-create';
    case USERS_EDIT = 'users-edit';
    case USERS_DELETE = 'users-delete';
}
