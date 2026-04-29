<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);

    $this->store = Store::factory()->create(['status' => 'active']);
});

function createVariantWithMinimumStock(int $minimumStock, array $overrides = []): ProductVariant
{
    return ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
        'minimum_stock_level' => $minimumStock,
        ...$overrides,
    ]);
}

function createVariantWithoutMinimumStock(array $overrides = []): ProductVariant
{
    return ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
        'minimum_stock_level' => null,
        ...$overrides,
    ]);
}

function createActiveBatch(ProductVariant $variant, Store $store, int $remaining = 50, array $overrides = []): Batch
{
    return Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'remaining_quantity' => $remaining,
        'initial_quantity' => $remaining,
        'status' => 'active',
        ...$overrides,
    ]);
}

function setExpiryAlertDays(int $days): void
{
    Setting::create([
        'key' => 'expiry_alert_days',
        'value' => (string) $days,
        'name' => 'Expiry Alert Days',
        'group' => 'general',
    ]);
    Cache::tags(['settings'])->flush();
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

it('admin can access inventory alerts index', function () {
    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/Alerts/Index')
            ->has('lowStockAlerts')
            ->has('expiryAlerts')
            ->has('summary')
            ->has('stores')
            ->has('filters')
        );
});

it('admin can access inventory alerts summary', function () {
    actingAs($this->admin)
        ->get(route('inventory.alerts.summary'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('summary')
        );
});

it('salesman without permission is denied from alerts index', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('inventory.alerts'))
        ->assertForbidden();
});

it('salesman without permission is denied from alerts summary', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('inventory.alerts.summary'))
        ->assertForbidden();
});

it('guest is redirected from alerts index', function () {
    get(route('inventory.alerts'))
        ->assertRedirect(route('login'));
});

it('guest is redirected from alerts summary', function () {
    get(route('inventory.alerts.summary'))
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Low-Stock Alerts
|--------------------------------------------------------------------------
*/

it('excludes variants with null minimum_stock_level from low-stock alerts', function () {
    $variant = createVariantWithoutMinimumStock();
    createActiveBatch($variant, $this->store, 5);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', [])
        );
});

it('includes variant where stock is below minimum_stock_level', function () {
    $variant = createVariantWithMinimumStock(20);
    createActiveBatch($variant, $this->store, 10);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', fn ($alerts) => count($alerts) === 1 && $alerts[0]['id'] === $variant->id)
        );
});

it('excludes variant where stock meets or exceeds minimum_stock_level', function () {
    $variant = createVariantWithMinimumStock(20);
    createActiveBatch($variant, $this->store, 25);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', [])
        );
});

it('aggregates stock across active and queued batches for low-stock check', function () {
    $variant = createVariantWithMinimumStock(20);
    createActiveBatch($variant, $this->store, 8);
    createActiveBatch($variant, $this->store, 5, ['status' => 'queued']);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', fn ($alerts) => count($alerts) === 1 && $alerts[0]['total_stock'] === 13)
        );
});

it('excludes closed batches from low-stock aggregation', function () {
    $variant = createVariantWithMinimumStock(5);
    createActiveBatch($variant, $this->store, 3);
    createActiveBatch($variant, $this->store, 50, ['status' => 'closed']);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', fn ($alerts) => count($alerts) === 1 && $alerts[0]['total_stock'] === 3)
        );
});

/*
|--------------------------------------------------------------------------
| Expiry Alerts
|--------------------------------------------------------------------------
*/

it('excludes batches with null expiry_date from expiry alerts', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, ['expiry_date' => null]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts', [])
        );
});

it('includes batch expiring within threshold', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(15)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts', fn ($alerts) => count($alerts) === 1)
        );
});

it('excludes batch expiring beyond threshold', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(60)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts', [])
        );
});

it('includes batch at exact threshold boundary', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts', fn ($alerts) => count($alerts) === 1)
        );
});

it('excludes closed batch even if expiring', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
        'status' => 'closed',
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts', [])
        );
});

it('excludes batch with zero remaining_quantity from expiry alerts', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 0, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts', [])
        );
});

it('returns empty expiry list when expiry_alert_days is zero', function () {
    setExpiryAlertDays(0);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDay()->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts', [])
        );
});

/*
|--------------------------------------------------------------------------
| Summary Endpoint
|--------------------------------------------------------------------------
*/

it('returns correct summary counts', function () {
    setExpiryAlertDays(30);

    $variant1 = createVariantWithMinimumStock(20);
    $variant2 = createVariantWithMinimumStock(15);
    createActiveBatch($variant1, $this->store, 5);
    createActiveBatch($variant2, $this->store, 3);

    $variant3 = createVariantWithMinimumStock(10);
    createActiveBatch($variant3, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts.summary'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.low_stock_count', 2)
            ->where('summary.expiry_count', 1)
            ->where('summary.total', 3)
        );
});

it('returns all zeros when no alerts exist', function () {
    actingAs($this->admin)
        ->get(route('inventory.alerts.summary'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.low_stock_count', 0)
            ->where('summary.expiry_count', 0)
            ->where('summary.total', 0)
        );
});

it('summary respects store_id filter', function () {
    setExpiryAlertDays(30);

    $store2 = Store::factory()->create(['status' => 'active']);

    $variant1 = createVariantWithMinimumStock(20);
    createActiveBatch($variant1, $this->store, 5);

    $variant2 = createVariantWithMinimumStock(15);
    createActiveBatch($variant2, $store2, 3);

    $variant3 = createVariantWithMinimumStock(10);
    createActiveBatch($variant3, $store2, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts.summary', ['store_id' => $store2->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.low_stock_count', 1)
            ->where('summary.expiry_count', 1)
            ->where('summary.total', 2)
        );
});

/*
|--------------------------------------------------------------------------
| Store Scoping via Query Parameter
|--------------------------------------------------------------------------
*/

it('admin with store_id param sees only that store alerts', function () {
    setExpiryAlertDays(30);

    $store2 = Store::factory()->create(['status' => 'active']);

    $variant1 = createVariantWithMinimumStock(20);
    createActiveBatch($variant1, $this->store, 5, ['expiry_date' => null]);
    createActiveBatch($variant1, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $variant2 = createVariantWithMinimumStock(15);
    createActiveBatch($variant2, $store2, 3, ['expiry_date' => null]);

    actingAs($this->admin)
        ->get(route('inventory.alerts', ['store_id' => $this->store->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', fn ($alerts) => count($alerts) === 1)
            ->where('expiryAlerts', fn ($alerts) => count($alerts) === 1)
        );
});

it('admin without store_id sees alerts from all stores', function () {
    setExpiryAlertDays(30);

    $store2 = Store::factory()->create(['status' => 'active']);

    $variant1 = createVariantWithMinimumStock(20);
    createActiveBatch($variant1, $this->store, 5, ['expiry_date' => null]);

    $variant2 = createVariantWithMinimumStock(10);
    createActiveBatch($variant2, $store2, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', fn ($alerts) => count($alerts) === 1)
            ->where('expiryAlerts', fn ($alerts) => count($alerts) === 1)
        );
});

/*
|--------------------------------------------------------------------------
| Salesman with Permission — Current Behavior
|--------------------------------------------------------------------------
| NOTE: The StockAlertController does NOT scope by the user's assigned store.
| It passes request()->integer('store_id') directly to the service. These
| tests document the ACTUAL behavior. When store scoping is implemented,
| update these tests accordingly.
*/

it('salesman with permission can access alerts index', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $salesman->givePermissionTo(PermissionsEnum::STOCK_ALERTS_VIEW->value);

    actingAs($salesman)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/Alerts/Index')
        );
});

it('salesman with permission sees alerts from all stores', function () {
    $store2 = Store::factory()->create(['status' => 'active']);

    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $salesman->givePermissionTo(PermissionsEnum::STOCK_ALERTS_VIEW->value);
    $salesman->stores()->attach($this->store);

    setExpiryAlertDays(30);

    $variant1 = createVariantWithMinimumStock(20);
    createActiveBatch($variant1, $this->store, 5);

    $variant2 = createVariantWithMinimumStock(10);
    createActiveBatch($variant2, $store2, 3);

    // Controller does not scope to salesman's store — sees all stores
    actingAs($salesman)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts', fn ($alerts) => count($alerts) === 2)
        );
});

/*
|--------------------------------------------------------------------------
| Inertia Props
|--------------------------------------------------------------------------
*/

it('passes only active stores to the view', function () {
    Store::factory()->create(['status' => 'inactive']);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stores', fn ($stores) => count($stores) === 1)
        );
});

it('passes store_id filter in filters prop', function () {
    actingAs($this->admin)
        ->get(route('inventory.alerts', ['store_id' => $this->store->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.store_id', $this->store->id)
        );
});

it('passes null store_id in filters when not provided', function () {
    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.store_id', null)
        );
});
