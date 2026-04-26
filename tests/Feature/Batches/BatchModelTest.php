<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReceptionOrder;
use App\Models\Store;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);
    $this->receptionOrder = ReceptionOrder::factory()->create();
});

function makeBatch(ProductVariant $variant, Store $store, ReceptionOrder $receptionOrder, array $overrides = []): Batch
{
    return Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'reception_order_id' => $receptionOrder->id,
        ...$overrides,
    ]);
}

it('expiry_status returns null when no expiry date', function () {
    $batch = makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => null,
    ]);

    expect($batch->expiry_status)->toBeNull();
});

it('expiry_status returns expired when date is in the past', function () {
    $batch = makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->subDay(),
    ]);

    expect($batch->expiry_status)->toBe('expired');
});

it('expiry_status returns expiring_soon when within threshold', function () {
    $batch = makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->addDays(15),
    ]);

    expect($batch->expiry_status)->toBe('expiring_soon');
});

it('expiry_status returns ok when beyond threshold', function () {
    $batch = makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->addMonths(6),
    ]);

    expect($batch->expiry_status)->toBe('ok');
});

it('scopeAvailable returns queued and active batches ordered by created_at', function () {
    $old = makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'status' => 'queued',
        'remaining_quantity' => 10,
        'created_at' => now()->subDay(),
    ]);
    $new = makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'status' => 'active',
        'remaining_quantity' => 20,
        'created_at' => now(),
    ]);
    makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'status' => 'closed',
        'remaining_quantity' => 5,
    ]);
    makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'status' => 'active',
        'remaining_quantity' => 0,
    ]);

    $results = Batch::available($this->variant->id, $this->store->id)->get();

    expect($results)->toHaveCount(2);
    expect($results->first()->id)->toBe($old->id);
    expect($results->last()->id)->toBe($new->id);
});

it('scopeExpiringSoon returns batches within threshold', function () {
    makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->addDays(10),
        'status' => 'active',
    ]);
    makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->addMonths(6),
        'status' => 'active',
    ]);
    makeBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->addDays(5),
        'status' => 'closed',
    ]);

    $results = Batch::expiringSoon(30)->get();

    expect($results)->toHaveCount(1);
});
