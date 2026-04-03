# Settings — Testing

## Test File Location
```
tests/Feature/Settings/SettingsTest.php
tests/Unit/Models/SettingModelTest.php
```

## Test Cases

```php
<?php

// tests/Feature/Settings/SettingsTest.php

use App\Enums\PermissionsEnum;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\usesTesting;

usesTesting();

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Cache::flush();
});

// ─── Seeder ───────────────────────────────────────────────────────────────────

it('seeds all Phase 1 settings with defaults', function () {
    expect(Setting::count())->toBe(5);
});

it('seeds correct defaults per business_name', function () {
    expect(Setting::where('key', 'business_name')->value('value'))->toBe('My Store');
});

it('seeds correct defaults for business_address', function () {
    expect(Setting::where('key', 'business_address')->value('value'))->toBe('');
});

it('seeds correct defaults for business_phone', function () {
    expect(Setting::where('key', 'business_phone')->value('value'))->toBe('');
});

it('seeds correct defaults for timezone', function () {
    expect(Setting::where('key', 'timezone')->value('value'))->toBe('UTC');
});

it('seeds correct defaults for tax_rate', function () {
    expect(Setting::where('key', 'tax_rate')->value('value'))->toBe('0');
});

it('does not overwrite admin-configured settings when re-seeded', function () {
    Setting::where('key', 'business_name')->update(['value' => 'Custom Business']);

    $this->seed(SettingsSeeder::class);
    expect(Setting::where('key', 'business_name')->value('value'))->toBe('Custom Business');
});

// ─── Access Control ───────────────────────────────────────────────────────────

it('allows admin to view settings page', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->get(route('settings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('settings.general')
            ->has('settings.tax')
        );
});

it('denies salesman access to settings page', function () {
    $salesman = User::factory()->create();

    $this->actingAs($salesman)
        ->get(route('settings'))
        ->assertForbidden();
});

it('denies unauthenticated access to settings page', function () {
    $this->get(route('settings'))
        ->assertRedirect(route('login'));
});

// ─── Update General Settings ──────────────────────────────────────────────────

it('admin can update general settings', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name'    => 'New Business Name',
            'business_address' => '456 Commerce Blvd',
            'business_phone'   => '+1 555 000 1234',
            'timezone'          => 'America/New_York',
        ])
        ->assertRedirect(route('settings'));

    expect(Setting::where('key', 'business_name')->value('value'))->toBe('New Business Name');
    expect(Setting::where('key', 'timezone')->value('value'))->toBe('America/New_York');
});

it('validates business_name is required for general settings', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => '',
            'timezone'      => 'UTC',
        ])
        ->assertSessionHasErrors('business_name');
});

it('validates timezone is a valid PHP timezone', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->put(route('settings.general.update'), [
            'business_name' => 'My Business',
            'timezone'      => 'Not/ATimezone',
        ])
        ->assertSessionHasErrors('timezone');
});

// ─── Update Tax Settings ──────────────────────────────────────────────────────

it('admin can update tax rate', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 8.5])
        ->assertRedirect(route('settings'));

    expect(Setting::where('key', 'tax_rate')->value('value'))->toBe('8.5');
});

it('validates tax_rate is numeric', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 'not-a-number'])
        ->assertSessionHasErrors('tax_rate');
});

it('validates tax_rate is between 0 and 100', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 150])
        ->assertSessionHasErrors('tax_rate');
});

it('validates tax_rate cannot be negative', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => -5])
        ->assertSessionHasErrors('tax_rate');
});

// ─── Cache Invalidation ───────────────────────────────────────────────────────

it('invalidates cache after updating settings', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo(PermissionsEnum::SETTINGS_MANAGE->value);

    // Prime the cache
    Setting::get('tax_rate');

    // Update the value
    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 15]);

    // Cache should be flushed; next read returns updated value
    expect(Setting::get('tax_rate'))->toBe(15.0);
});
```

```php
<?php

// tests/Unit/Models/SettingModelTest.php

use App\Models\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    Cache::flush();
});

// ─── Static Helper Methods ────────────────────────────────────────────────────

it('Setting::get returns string value for business_name', function () {
    expect(Setting::get('business_name'))->toBe('My Store');
});

it('Setting::get returns float for tax_rate', function () {
    Setting::where('key', 'tax_rate')->update(['value' => '8.25']);

    expect(Setting::get('tax_rate'))->toBe(8.25)
        ->and(Setting::get('tax_rate'))->toBeFloat();
});

it('Setting::get returns default value when key does not exist', function () {
    expect(Setting::get('non_existent_key', 'fallback'))->toBe('fallback');
});

it('Setting::set updates value and flushes cache', function () {
    Setting::get('business_name'); // prime cache

    Setting::set('business_name', 'Updated Business');

    expect(Setting::get('business_name'))->toBe('Updated Business');
});

it('Setting::group returns all keys for a group', function () {
    $group = Setting::group('tax');

    expect($group)->toHaveKey('tax_rate')
        ->and($group['tax_rate'])->toBe('0');
});
```

## Coverage Goals
- Seeder correctness (count, defaults, idempotency)
- Access control (admin allowed, salesman denied, guest redirected)
- Validation per group (required fields, numeric ranges, timezone validation)
- Successful update persists to database
- Cache flushed after every update
- `Setting::get()` returns correctly typed values (string, float)
- `Setting::set()` updates DB and flushes cache
- `Setting::group()` returns key-value array for a group
