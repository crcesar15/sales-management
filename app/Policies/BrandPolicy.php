<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\Brand;
use App\Models\User;

final class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsEnum::BRANDS_VIEW->value);
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can(PermissionsEnum::BRANDS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsEnum::BRANDS_CREATE->value);
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can(PermissionsEnum::BRANDS_EDIT->value);
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can(PermissionsEnum::BRANDS_DELETE->value);
    }

    public function restore(User $user, Brand $brand): bool
    {
        return $user->can(PermissionsEnum::BRANDS_RESTORE->value);
    }
}
