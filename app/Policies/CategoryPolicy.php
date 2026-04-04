<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\Category;
use App\Models\User;

final class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsEnum::CATEGORIES_VIEW->value);
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can(PermissionsEnum::CATEGORIES_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsEnum::CATEGORIES_CREATE->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can(PermissionsEnum::CATEGORIES_EDIT->value);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can(PermissionsEnum::CATEGORIES_DELETE->value);
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->can(PermissionsEnum::CATEGORIES_RESTORE->value);
    }
}
