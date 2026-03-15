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

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SettingsSeeder::class);
    Cache::flush();
});

// ─── Seeder ───────────────────────────────────────────────────────────────────

it('seeds all 10 settings with defaults', function () {
    expect(Setting::count())->toBe(10);
});

it('seeds tax_rate default as 0', function () {
    expect(Setting::where('key', 'tax_rate')->value('value'))->toBe('0');
});

it('seeds show_logo default as false', function () {
    expect(Setting::where('key', 'show_logo')->value('value'))->toBe('false');
});

it('does not overwrite existing settings when seeder is re-run', function () {
    Setting::where('key', 'store_name')->update(['value' => 'Custom Store']);

    $this->seed(SettingsSeeder::class);

    expect(Setting::where('key', 'store_name')->value('value'))->toBe('Custom Store');
});

// ─── Access Control ───────────────────────────────────────────────────────────

it('allows admin to view settings page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('settings.general')
            ->has('settings.tax')
            ->has('settings.receipt')
            ->has('settings.inventory')
        );
});

it('denies sales_rep access to settings page', function () {
    $salesRep = User::factory()->create()->assignRole('sales_rep');

    $this->actingAs($salesRep)
        ->get(route('settings.index'))
        ->assertForbidden();
});

it('denies unauthenticated access to settings page', function () {
    $this->get(route('settings.index'))
        ->assertRedirect(route('login'));
});

// ─── Update General Settings ──────────────────────────────────────────────────

it('admin can update general settings', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.general.update'), [
            'store_name'    => 'New Store Name',
            'store_address' => '456 Commerce Blvd',
            'store_phone'   => '+1 555 000 1234',
            'timezone'      => 'America/New_York',
        ])
        ->assertOk()
        ->assertJsonFragment(['message' => 'Settings updated successfully.']);

    expect(Setting::where('key', 'store_name')->value('value'))->toBe('New Store Name');
    expect(Setting::where('key', 'timezone')->value('value'))->toBe('America/New_York');
});

it('validates store_name is required for general settings', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.general.update'), [
            'store_name' => '',
            'timezone'   => 'UTC',
        ])
        ->assertSessionHasErrors('store_name');
});

it('validates timezone is a valid PHP timezone', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.general.update'), [
            'store_name' => 'My Store',
            'timezone'   => 'Not/ATimezone',
        ])
        ->assertSessionHasErrors('timezone');
});

// ─── Update Tax Settings ──────────────────────────────────────────────────────

it('admin can update tax rate', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 8.5])
        ->assertOk();

    expect(Setting::where('key', 'tax_rate')->value('value'))->toBe('8.5');
});

it('validates tax_rate is numeric', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 'not-a-number'])
        ->assertSessionHasErrors('tax_rate');
});

it('validates tax_rate is between 0 and 100', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 150])
        ->assertSessionHasErrors('tax_rate');
});

it('validates tax_rate cannot be negative', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => -5])
        ->assertSessionHasErrors('tax_rate');
});

// ─── Update Receipt Settings ──────────────────────────────────────────────────

it('admin can update receipt settings', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.receipt.update'), [
            'receipt_header' => 'Welcome!',
            'receipt_footer' => 'Thank you!',
            'show_logo'      => true,
        ])
        ->assertOk();

    expect(Setting::where('key', 'show_logo')->value('value'))->toBe('1'); // boolean true → "1"
    expect(Setting::where('key', 'receipt_header')->value('value'))->toBe('Welcome!');
});

it('validates show_logo is boolean', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.receipt.update'), [
            'receipt_header' => '',
            'receipt_footer' => '',
            'show_logo'      => 'maybe', // not boolean
        ])
        ->assertSessionHasErrors('show_logo');
});

// ─── Update Inventory Settings ────────────────────────────────────────────────

it('admin can update inventory thresholds', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.inventory.update'), [
            'low_stock_default_threshold' => 10,
            'expiry_alert_days'           => 14,
        ])
        ->assertOk();

    expect(Setting::where('key', 'low_stock_default_threshold')->value('value'))->toBe('10');
    expect(Setting::where('key', 'expiry_alert_days')->value('value'))->toBe('14');
});

it('validates low_stock_default_threshold is at least 1', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.inventory.update'), [
            'low_stock_default_threshold' => 0,
            'expiry_alert_days'           => 30,
        ])
        ->assertSessionHasErrors('low_stock_default_threshold');
});

it('validates expiry_alert_days cannot exceed 365', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.inventory.update'), [
            'low_stock_default_threshold' => 5,
            'expiry_alert_days'           => 400,
        ])
        ->assertSessionHasErrors('expiry_alert_days');
});

// ─── Cache Invalidation ───────────────────────────────────────────────────────

it('invalidates cache after updating settings', function () {
    $admin = User::factory()->create()->assignRole('admin');

    // Prime the cache
    Setting::get('tax_rate');

    // Update the value
    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 15]);

    // Cache should be flushed; next read returns updated value
    expect(Setting::get('tax_rate'))->toBe(15.0);
});

// ─── Public Endpoint ──────────────────────────────────────────────────────────

it('public settings endpoint is accessible without auth', function () {
    $this->get(route('settings.public'))
        ->assertOk()
        ->assertJsonStructure(['store_name', 'timezone']);
});

it('public settings does not expose sensitive settings', function () {
    $response = $this->get(route('settings.public'));

    $data = $response->json();
    expect(array_keys($data))->not->toContain('tax_rate')
        ->not->toContain('receipt_header')
        ->not->toContain('low_stock_default_threshold');
});

// ─── Activity Logging ─────────────────────────────────────────────────────────

it('logs settings update in activity log', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('settings.tax.update'), ['tax_rate' => 5]);

    $this->assertDatabaseHas('activity_log', [
        'log_name'    => 'settings',
        'description' => 'settings_updated',
        'causer_id'   => $admin->id,
    ]);
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

it('Setting::get returns the correct string value', function () {
    expect(Setting::get('store_name'))->toBe('My Store');
});

it('Setting::get returns a float for tax_rate', function () {
    Setting::where('key', 'tax_rate')->update(['value' => '8.25']);

    expect(Setting::get('tax_rate'))->toBe(8.25)
        ->and(Setting::get('tax_rate'))->toBeFloat();
});

it('Setting::get returns a bool for show_logo when true', function () {
    Setting::where('key', 'show_logo')->update(['value' => 'true']);

    expect(Setting::get('show_logo'))->toBeTrue()
        ->and(Setting::get('show_logo'))->toBeBool();
});

it('Setting::get returns a bool for show_logo when false', function () {
    expect(Setting::get('show_logo'))->toBeFalse()
        ->and(Setting::get('show_logo'))->toBeBool();
});

it('Setting::get returns an int for low_stock_default_threshold', function () {
    expect(Setting::get('low_stock_default_threshold'))->toBe(5)
        ->and(Setting::get('low_stock_default_threshold'))->toBeInt();
});

it('Setting::get returns a default value when key does not exist', function () {
    expect(Setting::get('non_existent_key', 'fallback'))->toBe('fallback');
});

it('Setting::set updates the value and flushes cache', function () {
    Setting::get('store_name'); // prime cache

    Setting::set('store_name', 'Updated Store');

    expect(Setting::get('store_name'))->toBe('Updated Store');
});

it('Setting::group returns all keys for a group', function () {
    $group = Setting::group('tax');

    expect($group)->toHaveKey('tax_rate')
        ->and($group['tax_rate'])->toBe('0');
});
```

## Coverage Goals
- Seeder correctness (count, defaults, idempotency)
- Access control (admin allowed, sales_rep denied, guest redirected)
- Validation per group (required fields, numeric ranges, timezone list, boolean type)
- Successful updates persist to the database
- Cache is flushed after every update
- `Setting::get()` returns correctly typed values (string, float, int, bool)
- `Setting::set()` updates DB and flushes cache
- Public endpoint accessible without auth; does not leak sensitive keys
- Activity logging on all setting updates
