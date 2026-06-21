# Testing Patterns

**Analysis Date:** 2026-06-21

## Test Framework

**Runner:**
- Pest 3 with Laravel plugin (`pestphp/pest: ^3.8`, `pestphp/pest-plugin-laravel: ^3.2`) — never PHPUnit-style tests
- Config: `phpunit.xml` (PHPUnit XML still drives suite bootstrap, but tests are Pest-style)
- `Mockery` (`mockery/mockery: ^1.4.4`) available for mocking
- `fakerphp/faker: ^1.9.1` for test data generation

**Assertion Library:**
- Pest expectations (`expect()->toBe()`, `expect()->toBeTrue()`, `expect()->toHaveCount()`)
- Laravel testing helpers (`assertStatus()`, `assertOk()`, `assertForbidden()`, `assertRedirect()`, `assertSessionHasErrors()`, `assertSoftDeleted()`, `assertDatabaseHas()`)
- Inertia testing plugin (`assertInertia(fn ($page) => $page->component(...)->has(...)->where(...))`)
- Pest Laravel function imports: `use function Pest\Laravel\actingAs;`, `use function Pest\Laravel\get;`, `use function Pest\Laravel\assertSoftDeleted;`, `use function Pest\Laravel\assertDatabaseHas;`

**Run Commands:**
```bash
php artisan test --compact                       # All tests
php artisan test --compact --filter=testName     # Single test by name
php artisan test --compact --filter=UsersTest    # Single file by class name
php artisan test --compact --filter=BatchTracking # Single suite by keyword
```
Create tests with `php artisan make:test --pest {Name}` (feature test by default); add `--unit` for unit tests.

## Test File Organization

**Location:**
- Feature tests: `tests/Feature/{Module}/{Module}Test.php` (e.g., `tests/Feature/Stores/StoreManagementTest.php`)
- Some flat feature tests live directly under `tests/Feature/` (e.g., `tests/Feature/BrandTest.php`, `tests/Feature/UserTest.php`, `tests/Feature/RoleTest.php`)
- Unit tests: `tests/Unit/Models/` (model behavior) and `tests/Unit/Services/{Module}/` (service behavior)
- No frontend tests (no Vitest/Jest test files for `resources/js/`)

**Naming:**
- PascalCase test files ending in `Test.php`: `BrandTest.php`, `BatchTrackingTest.php`, `StockAlertServiceTest.php`, `VendorTest.php`
- Test cases use `it('describes behavior in present tense', function () { ... });` — descriptive sentences, e.g., `it('admin user can list users')`, `it('filters by status')`, `it('guest is redirected to login')`

**Structure:**
```
tests/
├── Pest.php                          # Pest bootstrap (RefreshDatabase + seeders for Feature)
├── TestCase.php                      # Base TestCase (extends Illuminate\Foundation\Testing\TestCase)
├── Feature/
│   ├── Batches/
│   │   ├── BatchModelTest.php
│   │   └── BatchTrackingTest.php
│   ├── Inventory/
│   │   ├── StockAdjustmentTest.php
│   │   ├── StockAlertTest.php
│   │   └── StockOverviewTest.php
│   ├── Pos/
│   │   └── PosLayoutTest.php
│   ├── Settings/
│   │   └── SettingsTest.php
│   ├── StockTransfers/
│   │   └── StockTransferTest.php
│   ├── Stores/
│   │   ├── StoreManagementTest.php
│   │   └── StoreUserAssignmentTest.php
│   ├── Vendors/
│   │   ├── VendorCrudTest.php
│   │   └── VendorDeletionGuardTest.php
│   ├── BrandTest.php
│   ├── CatalogTest.php
│   ├── CategoryTest.php
│   ├── MeasurementUnitTest.php
│   ├── RoleTest.php
│   ├── UserActivityLogTest.php
│   └── UserTest.php
└── Unit/
    ├── Models/
    │   └── VendorTest.php
    └── Services/
        └── Inventory/
            └── StockAlertServiceTest.php
```

## Test Structure

**Suite Organization:**
```php
<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Brand;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\get;

// ─── Authorization ────────────────────────────────────────────────────────────

it('guest is redirected to login', function () {
    get(route('brands'))
        ->assertRedirect(route('login'));
});

it('user without permission receives 403', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->getJson(route('brands'))
        ->assertForbidden();
});

// ─── List ─────────────────────────────────────────────────────────────────────

it('returns paginated brands ordered by name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Brand::factory()->create(['name' => 'Zebra Corp']);
    Brand::factory()->create(['name' => 'Alpha Inc']);
    Brand::factory()->create(['name' => 'Middle LLC']);

    actingAs($admin)
        ->get(route('brands'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Brands/Index')
            ->where('brands.data.0.name', 'Alpha Inc')
            ->has('brands.meta')
        );
});
```
(From `tests/Feature/BrandTest.php`)

**Patterns:**
- **Setup:** Pest auto-seeds `RoleSeeder` and `PermissionSeeder` before every Feature test (configured in `tests/Pest.php`). Per-test setup creates the acting user inline and assigns a role: `$user = User::factory()->create(); $user->assignRole(RolesEnum::ADMIN);`
- **beforeEach for shared setup:** When multiple tests share fixtures, use `beforeEach`:
  ```php
  beforeEach(function () {
      $this->admin = User::factory()->create();
      $this->admin->assignRole(RolesEnum::ADMIN);
      $this->store = Store::factory()->create(['status' => 'active']);
      $this->variant = ProductVariant::factory()->create([...]);
  });
  ```
  (`tests/Feature/Batches/BatchTrackingTest.php`)
- **Helper functions defined inside test files** for repetitive model creation, placed at file top after `use` statements:
  ```php
  function createBatch(ProductVariant $variant, Store $store, ReceptionOrder $receptionOrder, array $overrides = []): Batch
  {
      return Batch::factory()->create([
          'product_variant_id' => $variant->id,
          'store_id' => $store->id,
          'reception_order_id' => $receptionOrder->id,
          ...$overrides,
      ]);
  }
  ```
- **Section dividers:** `/* ─── Section Name ─── */` or `/* | Section Name | */` blocks to group tests by concern (Access Control, List, Create, Update, Soft Delete, Restore, Permission Denials)
- **Teardown:** Not explicit — `RefreshDatabase` trait resets the in-memory SQLite DB between tests

**Unit tests** use explicit `uses()` call at file top (because `tests/Pest.php` only wires `RefreshDatabase` for `Feature`):
```php
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StockAlertService::class);
    $this->store = Store::factory()->create(['status' => 'active']);
});
```
(`tests/Unit/Services/Inventory/StockAlertServiceTest.php`)

## Mocking

**Framework:** Mockery (`mockery/mockery`) is available but rarely used — tests favor real database state via `RefreshDatabase` and factories

**Patterns:**
- Most tests do NOT mock — they use real Eloquent models, real services resolved via `app(StockAlertService::class)`, and in-memory SQLite (`phpunit.xml` sets `DB_DATABASE: ":memory:"`)
- Cache is flushed explicitly when testing cached code paths:
  ```php
  Setting::create(['key' => 'expiry_alert_days', 'value' => (string) $days, ...]);
  Cache::tags(['settings'])->flush();
  ```
  (`tests/Unit/Services/Inventory/StockAlertServiceTest.php`)

**What to Mock:**
- Not currently mocked in the observed test files. Prefer real models and real service instances.

**What NOT to Mock:**
- Models, services, and database — use factories + `RefreshDatabase` instead
- Roles/permissions — use the auto-seeded `RoleSeeder` and `PermissionSeeder` and assign via `$user->assignRole(RolesEnum::X)` / `$user->givePermissionTo(PermissionsEnum::X)`

## Fixtures and Factories

**Test Data:**
```php
// Simple factory call with overrides
Brand::factory()->create(['name' => 'Acme Corp']);
User::factory()->create();

// Nested factory calls for required foreign keys
ProductVariant::factory()->create([
    'product_id' => Product::factory()->create()->id,
]);

// Factory with overrides spread
Batch::factory()->create([
    'product_variant_id' => $variant->id,
    'store_id' => $store->id,
    'remaining_quantity' => $remaining,
    'status' => 'active',
    ...$overrides,
]);

// Inline faker via fake()
'first_name' => fake()->firstName,
'email' => fake()->email,
'username' => fake()->userName,
```

**Location:** `database/factories/` — one `*Factory.php` per model (e.g., `BrandFactory.php`, `UserFactory.php`, `BatchFactory.php`, `StoreFactory.php`)

**Factory conventions:**
- All factories are `final class` with `@extends Factory<Model>` PHPDoc annotation
- `definition(): array` returns default state using `fake()`
- Enum values use `->value`: `'status' => SalesOrderStatus::DRAFT->value`
- Factory states for specific scenarios (e.g., `UserFactory::unverified()` returns `$this->state(fn (array $attributes): array => ['email_verified_at' => null])`)
- Reusable password caching in `UserFactory`: `private static ?string $password = null;` then `self::$password ??= Hash::make('password')`
- Check for existing factory states before manually setting attributes in tests

## Coverage

**Requirements:** None enforced. No coverage threshold or coverage report generation detected.

**View Coverage:**
```bash
# No dedicated coverage command in composer scripts or package.json
# PHPUnit/Pest coverage is available but not configured:
php artisan test --coverage
```
PHPStan level 8 provides static-analysis coverage of `app/`, `routes/`, `database/`, `tests/`, `config/`, `public/`, `resources/`.

## Test Types

**Unit Tests:**
- Scope: isolated model behavior (casts, relationships, accessors) and service methods (query logic, aggregation)
- Approach: `uses(TestCase::class, RefreshDatabase::class);` at file top, resolve service via `app()` or test model directly
- Examples: `tests/Unit/Models/VendorTest.php` (casts `additional_contacts`/`meta` to array, `purchaseOrders` relationship), `tests/Unit/Services/Inventory/StockAlertServiceTest.php` (service `getLowStockAlerts()`, `getExpiryAlerts()`, `getSummary()`)
- Most tests should be feature tests — add `--unit` only when testing pure model/service logic

**Integration / Feature Tests:**
- Scope: HTTP endpoints (web Inertia pages + API JSON), authorization, validation, soft-delete, redirects, Inertia page props
- Approach: `actingAs($user)->get/post/put/delete(route(...))->assertStatus/assertInertia/assertRedirect/assertSessionHasErrors`
- Use `getJson()` / `postJson()` / `putJson()` / `deleteJson()` for API tests and for forbidden assertions (avoids `storage/logs/laravel.log` permission issues)
- Inertia assertions inspect rendered component + props:
  ```php
  ->assertInertia(fn ($page) => $page
      ->component('Batches/Index')
      ->has('batches')
      ->has('filters')
      ->has('stores')
      ->where('filters.status', 'active')
      ->has('batches.data', 1)
  );
  ```

**E2E Tests:**
- Not used — no Dusk, Playwright, or Cypress detected

## Common Patterns

**Authorization testing (canonical 3-tier pattern):**
```php
// 1. Guest redirected
it('guest is redirected to login', function () {
    get(route('brands'))->assertRedirect(route('login'));
});

// 2. Unauthorized user (wrong role) forbidden — use getJson to avoid log permission issues
it('user without permission receives 403', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);
    actingAs($user)->getJson(route('brands'))->assertForbidden();
});

// 3. Authorized user (admin role) succeeds
it('admin with permission can access the page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    actingAs($admin)->get(route('brands'))->assertOk()->assertInertia(...);
});
```

**Validation testing:**
```php
it('empty name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    actingAs($admin)
        ->post(route('brands.store'), ['name' => ''])
        ->assertSessionHasErrors(['name']);
});

it('name exceeding 50 chars returns validation error', function () {
    actingAs($admin)
        ->post(route('brands.store'), ['name' => str_repeat('a', 51)])
        ->assertSessionHasErrors(['name']);
});
```

**Soft-delete / restore testing:**
```php
it('admin deletes a brand', function () {
    $brand = Brand::factory()->create();
    actingAs($admin)->delete(route('brands.destroy', $brand))->assertRedirect(route('brands'));
    assertSoftDeleted($brand);
});

it('brand with active products cannot be deleted', function () {
    Product::factory()->create(['brand_id' => $brand->id, ...]);
    actingAs($admin)
        ->delete(route('brands.destroy', $brand))
        ->assertRedirect()->assertSessionHas('error');
    $brand->refresh();
    expect($brand->deleted_at)->toBeNull();
});
```

**API endpoint testing:**
```php
// Successful creation
actingAs($user)->post(route('api.v1.users.store'), $newUser)->assertStatus(201);

// Successful deletion
actingAs($user)->delete(route('api.v1.users.destroy', $newUser->id))->assertStatus(204);

// Validation failure
actingAs($user)->post(route('api.v1.users.store'), $newUser, ['Accept' => 'application/json'])->assertStatus(422);

// Forbidden
actingAs($user)->get(route('api.v1.users'))->assertStatus(403);
```
(From `tests/Feature/UserTest.php`)

**State verification after mutation:**
```php
$latestUser = User::with('roles')->latest('id')->firstOrFail();
expect($latestUser->first_name)->toBe($newUser['first_name']);
expect($latestUser->roles[0]->id)->toBe($newUser['roles'][0]);
```

**Async Testing:**
- Not applicable to backend Pest tests (synchronous `QUEUE_CONNECTION=sync` in `phpunit.xml`)
- No frontend async test framework

**Error Testing:**
- Forbidden: `->assertForbidden()` or `->assertStatus(403)`
- Validation: `->assertSessionHasErrors(['field'])` (web) or `->assertStatus(422)` (API with JSON accept header)
- Redirect on error: `->assertRedirect()->assertSessionHas('error')` (catches `Exception` from service)
- Guest redirect: `->assertRedirect(route('login'))`

## Seeder Dependencies

- `tests/Pest.php` auto-seeds `RoleSeeder` and `PermissionSeeder` `beforeEach` for ALL `Feature` tests via:
  ```php
  pest()->extend(TestCase::class)
      ->use(RefreshDatabase::class)
      ->in('Feature')
      ->beforeEach(function () {
          $test = $this;
          $test->seed(RoleSeeder::class);
          $test->seed(PermissionSeeder::class);
      });
  ```
- Unit tests do NOT auto-seed — they only get `RefreshDatabase` if explicitly added via `uses()`
- When creating test users, assign roles explicitly: `$user->assignRole(RolesEnum::ADMIN)` or `$user->assignRole(RolesEnum::SALESMAN)`
- When testing granular permissions, assign only the permissions needed for the test: `$user->givePermissionTo(PermissionsEnum::X)`
- Re-seed permissions after enum changes: `php artisan db:seed --class=PermissionSeeder`

## Test Environment

**Configuration (`phpunit.xml`):**
- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` — fast in-memory DB
- `CACHE_DRIVER=array`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`
- `BCRYPT_ROUNDS=4` (faster hashing)
- `PULSE_ENABLED=false`, `TELESCOPE_ENABLED=false`

## Known Issues

- `storage/logs/laravel.log` has permission issues during tests — use `getJson()` instead of `get()` for forbidden (403) assertions to avoid log write failures that cause cascading test errors
- No coverage target enforced — coverage gaps exist for many services (`app/Services/` has 24+ services; only `StockAlertService` has a dedicated unit test)
- No frontend test framework — Vue components, composables, and TypeScript types are untested

---

*Testing analysis: 2026-06-21*