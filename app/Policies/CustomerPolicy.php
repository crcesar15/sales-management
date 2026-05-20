<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\Customer;
use App\Models\User;

final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsEnum::CUSTOMERS_VIEW->value);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can(PermissionsEnum::CUSTOMERS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsEnum::CUSTOMERS_CREATE->value);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can(PermissionsEnum::CUSTOMERS_EDIT->value);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can(PermissionsEnum::CUSTOMERS_DELETE->value);
    }
}
