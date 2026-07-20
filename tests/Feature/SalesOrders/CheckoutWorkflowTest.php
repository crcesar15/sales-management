<?php

declare(strict_types=1);

use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use App\Services\SalesOrderService;

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    $this->actor = User::factory()->create();
    $this->actor->stores()->attach($this->store);
    $this->variant = ProductVariant::factory()->create(['product_id' => Product::factory(), 'price' => 100]);
    $this->batch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'initial_quantity' => 10,
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);
    $this->service = app(SalesOrderService::class);
    $this->createDraft = fn (): SalesOrder => $this->service->create([
        'store_id' => $this->store->id,
        'items' => [[
            'product_variant_id' => $this->variant->id,
            'sale_unit_id' => null,
            'quantity' => 2,
            'unit_price' => 100,
        ]],
    ], $this->actor);
});

it('creates a minimal pending-payment draft, confirms it, and applies only the order total from cash', function (): void {
    $order = ($this->createDraft)();

    expect($order->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payment_status)->toBe(SalesOrderPaymentStatus::PENDING);

    $this->service->confirm($order, $this->actor);
    expect($order->refresh()->status)->toBe(SalesOrderStatus::CONFIRMED)
        ->and($this->batch->refresh()->remaining_quantity)->toBe(8);

    $this->service->pay($order, [['payment_method' => 'cash', 'amount' => 250, 'reference' => null]], $this->actor);
    expect($order->refresh()->payment_status)->toBe(SalesOrderPaymentStatus::PAID)
        ->and((float) $order->payments()->sum('amount'))->toBe(200.0);
});

it('restores allocated stock when an unpaid confirmed order is cancelled', function (): void {
    $order = ($this->createDraft)();
    $this->service->confirm($order, $this->actor);
    $this->service->cancel($order, null, $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::CANCELLED)
        ->and($this->batch->refresh()->remaining_quantity)->toBe(10)
        ->and($order->items()->firstOrFail()->stockAllocations()->whereNotNull('restored_at')->count())->toBe(1);
});
