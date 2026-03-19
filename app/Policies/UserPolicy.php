<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\User;

final class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsEnum::USERS_VIEW->value);
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $target): bool
    {
        return $user->can(PermissionsEnum::USERS_VIEW->value);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionsEnum::USERS_CREATE->value);
    }

    /**
     * Determine whether the user can update a user.
     */
    public function update(User $user, User $target): bool
    {
        return $user->can(PermissionsEnum::USERS_EDIT->value)
            && $user->id !== $target->id;
    }

    /**
     * Determine whether the user can delete a user.
     * A user cannot delete themselves.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->can(PermissionsEnum::USERS_DELETE->value)
            && $user->id !== $target->id;
    }

    /**
     * Determine whether the user can restore a soft-deleted user.
     */
    public function restore(User $user, User $target): bool
    {
        return $user->can(PermissionsEnum::USERS_DELETE->value);
    }

    /**
     * Determine whether the user can manage (assign/remove) stores for a user.
     */
    public function manageStores(User $user, User $target): bool
    {
        return $user->can(PermissionsEnum::USERS_EDIT->value);
    }
}
