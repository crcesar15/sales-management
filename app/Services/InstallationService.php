<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RolesEnum;
use App\Enums\UserLocale;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\InitialData\PharmacySpanishSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class InstallationService
{
    public function hasAdministrator(): bool
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RolesEnum::ADMIN->value))
            ->exists();
    }

    /** @param array{business_name: string, first_name: string, last_name: string, username: string, email: string, password: string} $data */
    public function installPharmacySpanish(array $data): User
    {
        if ($this->hasAdministrator()) {
            throw new InvalidArgumentException('La instalacion ya tiene un administrador.');
        }

        return DB::transaction(function () use ($data): User {
            app(PharmacySpanishSeeder::class)->run();

            Setting::set('business_name', $data['business_name']);
            $store = Store::query()->where('code', 'CM')->firstOrFail();
            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active',
                'locale' => UserLocale::SPANISH,
            ]);

            $user->assignRole(RolesEnum::ADMIN->value);
            $user->stores()->attach($store);
            Cache::forget('settings');

            return $user->load(['roles', 'stores']);
        });
    }

    public function seedPharmacySpanish(): void
    {
        app(PharmacySpanishSeeder::class)->run();
    }
}
