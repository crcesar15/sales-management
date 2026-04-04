<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\MeasurementUnit;
use App\Models\User;

final class MeasurementUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionsEnum::MEASUREMENT_UNITS_VIEW->value);
    }

    public function view(User $user, MeasurementUnit $measurementUnit): bool
    {
        return $user->can(PermissionsEnum::MEASUREMENT_UNITS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionsEnum::MEASUREMENT_UNITS_CREATE->value);
    }

    public function update(User $user, MeasurementUnit $measurementUnit): bool
    {
        return $user->can(PermissionsEnum::MEASUREMENT_UNITS_EDIT->value);
    }

    public function delete(User $user, MeasurementUnit $measurementUnit): bool
    {
        return $user->can(PermissionsEnum::MEASUREMENT_UNITS_DELETE->value);
    }

    public function restore(User $user, MeasurementUnit $measurementUnit): bool
    {
        return $user->can(PermissionsEnum::MEASUREMENT_UNITS_RESTORE->value);
    }
}
