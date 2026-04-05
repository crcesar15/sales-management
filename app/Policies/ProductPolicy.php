<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\Product;
use App\Models\User;

final class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsEnum::PRODUCTS_VIEW->value);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can(PermissionsEnum::PRODUCTS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsEnum::PRODUCTS_CREATE->value);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can(PermissionsEnum::PRODUCTS_EDIT->value);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can(PermissionsEnum::PRODUCTS_DELETE->value);
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->can(PermissionsEnum::PRODUCTS_RESTORE->value);
    }
}
