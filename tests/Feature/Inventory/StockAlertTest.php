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
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);

    $this->store = Store::factory()->create(['status' => 'active']);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createVariantWithMinimumStock(int $minimumStock, array $overrides = []): ProductVariant
{
    return ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
        'minimum_stock_level' => $minimumStock,
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createVariantWithoutMinimumStock(array $overrides = []): ProductVariant
{
    return ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
        'minimum_stock_level' => null,
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createActiveBatch(ProductVariant $variant, Store $store, int $remaining = 50, array $overrides = []): Batch
{
    $batch = Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'remaining_quantity' => $remaining,
        'initial_quantity' => $remaining,
        'status' => 'active',
        ...$overrides,
    ]);

    $variant->recalculateStock();

    return $batch;
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

it('does not register a separate inventory alerts summary route', function () {
    expect(Route::has('inventory.alerts.summary'))->toBeFalse();
});

it('salesman without permission is denied from alerts index', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('inventory.alerts'))
        ->assertForbidden();
});

it('guest is redirected from alerts index', function () {
    get(route('inventory.alerts'))
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
            ->where('lowStockAlerts.data', [])
        );
});

it('includes variant where stock is below minimum_stock_level', function () {
    $variant = createVariantWithMinimumStock(20);
    createActiveBatch($variant, $this->store, 10);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.data', fn ($alerts) => count($alerts) === 1 && $alerts[0]['id'] === $variant->id)
        );
});

it('excludes variant where stock meets or exceeds minimum_stock_level', function () {
    $variant = createVariantWithMinimumStock(20);
    createActiveBatch($variant, $this->store, 25);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.data', [])
        );
});

it('aggregates stock across active batches for low-stock check', function () {
    $variant = createVariantWithMinimumStock(20);
    createActiveBatch($variant, $this->store, 8);
    createActiveBatch($variant, $this->store, 5);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.data', fn ($alerts) => count($alerts) === 1
                && $alerts[0]['total_stock'] === 13
                && $alerts[0]['shortage_quantity'] === 7
                && array_key_exists('product_name', $alerts[0])
                && array_key_exists('brand_name', $alerts[0]))
        );
});

it('includes an out-of-stock variant with only closed batches when filtered by store', function () {
    $variant = createVariantWithMinimumStock(5);
    createActiveBatch($variant, $this->store, 0, ['status' => 'closed']);

    actingAs($this->admin)
        ->get(route('inventory.alerts', ['store_id' => $this->store->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.data', fn ($alerts) => count($alerts) === 1 && $alerts[0]['total_stock'] === 0)
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
            ->where('lowStockAlerts.data', fn ($alerts) => count($alerts) === 1 && $alerts[0]['total_stock'] === 3)
        );
});

it('derives the summary from all matching alerts', function () {
    setExpiryAlertDays(30);

    $lowStockVariant = createVariantWithMinimumStock(20);
    createActiveBatch($lowStockVariant, $this->store, 5);

    $expiringVariant = createVariantWithMinimumStock(10);
    createActiveBatch($expiringVariant, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.low_stock_count', 1)
            ->where('summary.expiry_count', 1)
            ->where('summary.total', 2)
        );
});

it('orders low-stock alerts by out-of-stock then greatest shortage', function () {
    $outOfStock = createVariantWithMinimumStock(5);
    createActiveBatch($outOfStock, $this->store, 0, ['status' => 'closed']);

    $largestShortage = createVariantWithMinimumStock(20);
    createActiveBatch($largestShortage, $this->store, 5);

    $smallestShortage = createVariantWithMinimumStock(10);
    createActiveBatch($smallestShortage, $this->store, 5);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.data', fn ($alerts) => $alerts->pluck('id')->all() === [
                $outOfStock->id,
                $largestShortage->id,
                $smallestShortage->id,
            ])
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
            ->where('expiryAlerts.data', [])
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
            ->where('expiryAlerts.data', fn ($alerts) => count($alerts) === 1)
        );
});

it('serializes batch identity for expiry alerts', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'batch_identifier' => 'LOT-2026-001',
        'expiry_date' => now()->addDays(15)->toDateString(),
    ]);

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('expiryAlerts.data.0.batch_identifier', 'LOT-2026-001')
            ->where('expiryAlerts.data.0.store.id', $this->store->id)
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
            ->where('expiryAlerts.data', [])
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
            ->where('expiryAlerts.data', fn ($alerts) => count($alerts) === 1)
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
            ->where('expiryAlerts.data', [])
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
            ->where('expiryAlerts.data', [])
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
            ->where('expiryAlerts.data', [])
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
            ->where('lowStockAlerts.data', fn ($alerts) => count($alerts) === 1)
            ->where('expiryAlerts.data', fn ($alerts) => count($alerts) === 1)
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
            ->where('lowStockAlerts.data', fn ($alerts) => count($alerts) === 1)
            ->where('expiryAlerts.data', fn ($alerts) => count($alerts) === 1)
        );
});

it('admin without store_id sees alerts only from active stores', function () {
    setExpiryAlertDays(30);

    $activeVariant = createVariantWithMinimumStock(20);
    createActiveBatch($activeVariant, $this->store, 5, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $inactiveStore = Store::factory()->create(['status' => 'inactive']);
    $inactiveVariant = createVariantWithMinimumStock(20);
    createActiveBatch($inactiveVariant, $inactiveStore, 5, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $deletedStore = Store::factory()->create(['status' => 'active']);
    $deletedVariant = createVariantWithMinimumStock(20);
    createActiveBatch($deletedVariant, $deletedStore, 5, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);
    $deletedStore->delete();

    actingAs($this->admin)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.data', fn ($alerts) => $alerts->pluck('id')->all() === [$activeVariant->id])
            ->where('expiryAlerts.data', fn ($alerts) => count($alerts) === 1 && $alerts[0]['store_id'] === $this->store->id)
            ->where('summary.low_stock_count', 1)
            ->where('summary.expiry_count', 1)
        );
});

/*
|--------------------------------------------------------------------------
| Salesman Store Scope
|--------------------------------------------------------------------------
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

it('salesman with permission sees alerts only from assigned stores', function () {
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

    actingAs($salesman)
        ->get(route('inventory.alerts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.data', fn ($alerts) => count($alerts) === 1 && $alerts[0]['id'] === $variant1->id)
            ->where('stores', fn ($stores) => count($stores) === 1 && $stores[0]['id'] === $this->store->id)
        );
});

it('denies a salesman access to an unassigned store filter', function () {
    $store2 = Store::factory()->create(['status' => 'active']);
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $salesman->givePermissionTo(PermissionsEnum::STOCK_ALERTS_VIEW->value);
    $salesman->stores()->attach($this->store);

    actingAs($salesman)
        ->getJson(route('inventory.alerts', ['store_id' => $store2->id]))
        ->assertForbidden();
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

it('paginates both alert lists independently', function () {
    setExpiryAlertDays(30);

    for ($index = 1; $index <= 3; $index++) {
        $lowStockVariant = createVariantWithMinimumStock(10);
        createActiveBatch($lowStockVariant, $this->store, $index);

        $expiryVariant = createVariantWithMinimumStock(1);
        createActiveBatch($expiryVariant, $this->store, 10, [
            'batch_identifier' => "LOT-{$index}",
            'expiry_date' => now()->addDays($index)->toDateString(),
        ]);
    }

    actingAs($this->admin)
        ->get(route('inventory.alerts', [
            'tab' => 'expiry',
            'low_stock_page' => 2,
            'low_stock_per_page' => 1,
            'expiry_page' => 2,
            'expiry_per_page' => 1,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.tab', 'expiry')
            ->where('lowStockAlerts.meta.current_page', 2)
            ->where('lowStockAlerts.meta.per_page', 1)
            ->where('lowStockAlerts.meta.total', 3)
            ->has('lowStockAlerts.data', 1)
            ->where('expiryAlerts.meta.current_page', 2)
            ->where('expiryAlerts.meta.per_page', 1)
            ->where('expiryAlerts.meta.total', 3)
            ->has('expiryAlerts.data', 1)
        );
});

it('bounds alert page sizes', function () {
    actingAs($this->admin)
        ->get(route('inventory.alerts', [
            'low_stock_per_page' => 0,
            'expiry_per_page' => 101,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('lowStockAlerts.meta.per_page', 1)
            ->where('expiryAlerts.meta.per_page', 100)
        );
});
