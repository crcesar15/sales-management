<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Inventory;

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Store;
use App\Services\StockAlertService;
use Illuminate\Support\Facades\Cache;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StockAlertService::class);
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
| getLowStockAlerts()
|--------------------------------------------------------------------------
*/

it('excludes variants with null minimum_stock_level', function () {
    $variant = createVariantWithoutMinimumStock();
    createActiveBatch($variant, $this->store, 5);

    $results = $this->service->getLowStockAlerts();

    expect($results)->toHaveCount(0);
});

it('includes variants where total stock is below minimum_stock_level', function () {
    $variant = createVariantWithMinimumStock(20);
    createActiveBatch($variant, $this->store, 10);

    $results = $this->service->getLowStockAlerts();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($variant->id);
    expect($results->first()->total_stock)->toBe(10);
});

it('excludes variants where total stock meets or exceeds minimum_stock_level', function () {
    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 15);

    $results = $this->service->getLowStockAlerts();

    expect($results)->toHaveCount(0);
});

it('applies store filter correctly', function () {
    $store2 = Store::factory()->create(['status' => 'active']);

    $variant = createVariantWithMinimumStock(5);
    createActiveBatch($variant, $this->store, 2);
    createActiveBatch($variant, $store2, 2);

    $results = $this->service->getLowStockAlerts($this->store->id);

    expect($results)->toHaveCount(1);
    expect($results->first()->total_stock)->toBe(2);
});

it('aggregates stock across multiple batches for the same variant', function () {
    $variant = createVariantWithMinimumStock(15);
    createActiveBatch($variant, $this->store, 5);
    createActiveBatch($variant, $this->store, 3);
    createActiveBatch($variant, $this->store, 2, ['status' => 'queued']);

    $results = $this->service->getLowStockAlerts();

    expect($results)->toHaveCount(1);
    expect($results->first()->total_stock)->toBe(10);
});

it('returns empty collection when no variants meet criteria', function () {
    $results = $this->service->getLowStockAlerts();

    expect($results)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| getExpiryAlerts()
|--------------------------------------------------------------------------
*/

it('excludes batches with null expiry_date', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, ['expiry_date' => null]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(0);
});

it('includes batches expiring within threshold', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(10)->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(1);
});

it('excludes batches beyond threshold', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(45)->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(0);
});

it('excludes closed batches', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
        'status' => 'closed',
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(0);
});

it('excludes batches with zero remaining_quantity', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 0, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(0);
});

it('applies store filter', function () {
    setExpiryAlertDays(30);

    $store2 = Store::factory()->create(['status' => 'active']);

    $variant1 = createVariantWithMinimumStock(10);
    $variant2 = createVariantWithMinimumStock(10);
    createActiveBatch($variant1, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);
    createActiveBatch($variant2, $store2, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts($this->store->id);

    expect($results)->toHaveCount(1);
    expect($results->first()->store_id)->toBe($this->store->id);
});

it('returns empty collection when threshold is zero', function () {
    setExpiryAlertDays(0);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDay()->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(0);
});

it('orders results by expiry_date ascending', function () {
    setExpiryAlertDays(90);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(60)->toDateString(),
    ]);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(10)->toDateString(),
    ]);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(30)->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(3);
    expect($results->get(0)->expiry_date->lessThan($results->get(1)->expiry_date))->toBeTrue();
    expect($results->get(1)->expiry_date->lessThan($results->get(2)->expiry_date))->toBeTrue();
});

it('eager loads productVariant.product and store relationships', function () {
    setExpiryAlertDays(30);

    $variant = createVariantWithMinimumStock(10);
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(1);
    $batch = $results->first();
    expect($batch->relationLoaded('productVariant'))->toBeTrue();
    expect($batch->productVariant->relationLoaded('product'))->toBeTrue();
    expect($batch->relationLoaded('store'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| getSummary()
|--------------------------------------------------------------------------
*/

it('returns correct counts matching individual query results', function () {
    setExpiryAlertDays(30);

    $variant1 = createVariantWithMinimumStock(20);
    $variant2 = createVariantWithMinimumStock(15);
    $variant3 = createVariantWithMinimumStock(10);
    createActiveBatch($variant1, $this->store, 5);
    createActiveBatch($variant2, $this->store, 3);
    createActiveBatch($variant3, $this->store, 10, [
        'expiry_date' => now()->addDays(5)->toDateString(),
    ]);
    createActiveBatch($variant3, $this->store, 10, [
        'expiry_date' => now()->addDays(10)->toDateString(),
    ]);

    $summary = $this->service->getSummary();

    expect($summary)->toBe([
        'low_stock_count' => 2,
        'expiry_count' => 2,
        'total' => 4,
    ]);
});

it('returns all zeros when no alerts exist', function () {
    $summary = $this->service->getSummary();

    expect($summary)->toBe([
        'low_stock_count' => 0,
        'expiry_count' => 0,
        'total' => 0,
    ]);
});

it('applies store filter to both counts', function () {
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

    $summary = $this->service->getSummary($store2->id);

    expect($summary['low_stock_count'])->toBe(1);
    expect($summary['expiry_count'])->toBe(1);
    expect($summary['total'])->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Default Threshold Behavior
|--------------------------------------------------------------------------
*/

it('uses default 30-day threshold when setting is not configured', function () {
    $variant = createVariantWithMinimumStock(10);

    // Batch at day 25 should be included (within default 30 days)
    createActiveBatch($variant, $this->store, 10, [
        'expiry_date' => now()->addDays(25)->toDateString(),
    ]);

    // Batch at day 35 should be excluded (beyond default 30 days)
    $variant2 = createVariantWithMinimumStock(10);
    createActiveBatch($variant2, $this->store, 10, [
        'expiry_date' => now()->addDays(35)->toDateString(),
    ]);

    $results = $this->service->getExpiryAlerts();

    expect($results)->toHaveCount(1);
    expect($results->first()->product_variant_id)->toBe($variant->id);
});
