<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(SettingsSeeder::class);
    Cache::flush();
});

// ─── Seeder ───────────────────────────────────────────────────────────────────

it('seeds all Phase 1 settings with defaults', function () {
    expect(Setting::count())->toBe(5);
});

it('seeds correct default for business_name', function () {
    expect(Setting::where('key', 'business_name')->value('value'))->toBe('My Store');
});

it('seeds correct default for business_address', function () {
    expect(Setting::where('key', 'business_address')->value('value'))->toBe('');
});

it('seeds correct default for business_phone', function () {
    expect(Setting::where('key', 'business_phone')->value('value'))->toBe('');
});

it('seeds correct default for timezone', function () {
    expect(Setting::where('key', 'timezone')->value('value'))->toBe('UTC');
});

it('seeds correct default for tax_rate', function () {
    expect(Setting::where('key', 'tax_rate')->value('value'))->toBe('0');
});

it('does not overwrite admin-configured settings when re-seeded', function () {
    Setting::where('key', 'business_name')->update(['value' => 'Custom Business']);

    seed(SettingsSeeder::class);
    expect(Setting::where('key', 'business_name')->value('value'))->toBe('Custom Business');
});

// ─── Access Control ───────────────────────────────────────────────────────────

it('allows admin with permission to view settings page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->get(route('settings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('settings.general')
            ->has('settings.tax')
        );
});

it('denies user without permission access to settings page', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('settings'))
        ->assertForbidden();
});

it('denies unauthenticated access to settings page', function () {
    $this->get(route('settings'))
        ->assertRedirect(route('login'));
});

// ─── Update General Settings ──────────────────────────────────────────────────

it('admin can update general settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => 'New Business Name',
            'business_address' => '456 Commerce Blvd',
            'business_phone' => '+1 555 000 1234',
            'timezone' => 'America/New_York',
        ])
        ->assertRedirect(route('settings'));

    expect(Setting::where('key', 'business_name')->value('value'))->toBe('New Business Name');
    expect(Setting::where('key', 'business_address')->value('value'))->toBe('456 Commerce Blvd');
    expect(Setting::where('key', 'business_phone')->value('value'))->toBe('+1 555 000 1234');
    expect(Setting::where('key', 'timezone')->value('value'))->toBe('America/New_York');
});

it('validates business_name is required', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => '',
            'timezone' => 'UTC',
        ])
        ->assertSessionHasErrors('business_name');
});

it('validates business_name max length is 100', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => str_repeat('a', 101),
            'timezone' => 'UTC',
        ])
        ->assertSessionHasErrors('business_name');
});

it('validates timezone is a valid PHP timezone', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => 'My Business',
            'timezone' => 'Not/ATimezone',
        ])
        ->assertSessionHasErrors('timezone');
});

it('validates business_address max length is 500', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => 'Valid Name',
            'timezone' => 'UTC',
            'business_address' => str_repeat('a', 501),
        ])
        ->assertSessionHasErrors('business_address');
});

it('validates business_phone max length is 30', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => 'Valid Name',
            'timezone' => 'UTC',
            'business_phone' => str_repeat('1', 31),
        ])
        ->assertSessionHasErrors('business_phone');
});

// ─── Update Tax Settings ──────────────────────────────────────────────────────

it('admin can update tax rate', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 8.5])
        ->assertRedirect(route('settings'));

    expect(Setting::where('key', 'tax_rate')->value('value'))->toBe('8.5');
});

it('validates tax_rate is required', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.tax.update'), [])
        ->assertSessionHasErrors('tax_rate');
});

it('validates tax_rate is numeric', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 'not-a-number'])
        ->assertSessionHasErrors('tax_rate');
});

it('validates tax_rate max is 100', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 150])
        ->assertSessionHasErrors('tax_rate');
});

it('validates tax_rate cannot be negative', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => -5])
        ->assertSessionHasErrors('tax_rate');
});

// ─── Cache Invalidation ───────────────────────────────────────────────────────

it('invalidates cache after updating settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    // Prime the cache
    Setting::get('tax_rate');

    // Update via the endpoint (which flushes cache via service)
    actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 15]);

    expect(Setting::get('tax_rate'))->toBe(15.0);
});
