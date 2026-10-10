<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UserPreferenceService
{
    /**
     * @param  array<string, string>  $preferences
     */
    public function update(User $user, array $preferences): User
    {
        return DB::transaction(function () use ($user, $preferences): User {
            $user->update($preferences);

            return $user->refresh();
        });
    }
}
