<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\StockAdjustment;
use App\Models\User;

final class StockAdjustmentPolicy
{
    public function view(User $user, StockAdjustment $adjustment): bool
    {
        return $user->can(PermissionsEnum::STOCK_ADJUST->value)
            && ($user->hasRole(RolesEnum::ADMIN->value) || $user->id === $adjustment->user_id);
    }
}
