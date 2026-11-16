<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\CashRegister;
use App\Models\MeasurementUnit;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\InstallationService;

it('installs the Spanish pharmacy profile with an administrator', function () {
    $admin = app(InstallationService::class)->installPharmacySpanish([
        'business_name' => 'Farmacia Salud',
        'first_name' => 'Maria',
        'last_name' => 'Lopez',
        'username' => 'maria.lopez',
        'email' => 'maria@example.com',
        'password' => 'secure-password',
    ]);

    $store = Store::query()->where('code', 'CM')->sole();

    expect($admin->hasRole(RolesEnum::ADMIN->value))->toBeTrue()
        ->and($admin->stores()->whereKey($store->id)->exists())->toBeTrue()
        ->and(Setting::get('business_name'))->toBe('Farmacia Salud')
        ->and(Setting::get('currency'))->toBe('BOB')
        ->and(Setting::get('tax_rate'))->toBe(0.0)
        ->and(MeasurementUnit::query()->where('name', 'Tableta')->value('abbreviation'))->toBe('Tab.')
        ->and(CashRegister::query()->where('store_id', $store->id)->where('code', 'CJ1')->value('is_default'))->toBeTrue();
});

it('grants POS operating permissions to salesmen', function () {
    app(InstallationService::class)->seedPharmacySpanish();

    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN->value);

    expect($salesman->can(PermissionsEnum::POS_ACCESS->value))->toBeTrue()
        ->and($salesman->can(PermissionsEnum::SHIFTS_OPEN->value))->toBeTrue()
        ->and($salesman->can(PermissionsEnum::SHIFTS_CLOSE->value))->toBeTrue()
        ->and($salesman->can(PermissionsEnum::CASH_MOVEMENTS_CREATE->value))->toBeTrue()
        ->and($salesman->can(PermissionsEnum::CUSTOMERS_VIEW->value))->toBeTrue()
        ->and($salesman->can(PermissionsEnum::CUSTOMERS_CREATE->value))->toBeTrue()
        ->and($salesman->can(PermissionsEnum::SETTINGS_MANAGE->value))->toBeFalse();
});

it('does not duplicate the pharmacy initial data on subsequent runs', function () {
    $service = app(InstallationService::class);
    $service->seedPharmacySpanish();
    $service->seedPharmacySpanish();

    expect(Store::query()->where('code', 'CM')->count())->toBe(1)
        ->and(CashRegister::query()->where('code', 'CJ1')->count())->toBe(1)
        ->and(MeasurementUnit::query()->where('name', 'Capsula')->count())->toBe(1);
});
