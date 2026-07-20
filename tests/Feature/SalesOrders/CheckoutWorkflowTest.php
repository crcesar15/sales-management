<?php

declare(strict_types=1);

use App\Enums\CashRegisterShiftStatus;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Batch;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Customer;
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
    $this->shift = CashRegisterShift::factory()->create([
        'cash_register_id' => CashRegister::factory()->create(['store_id' => $this->store])->id,
        'user_id' => $this->actor->id,
        'status' => CashRegisterShiftStatus::OPEN->value,
    ]);
    $this->variant = ProductVariant::factory()->create(['product_id' => Product::factory(), 'price' => 100]);
    $this->batch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonth()->toDateString(),
        'initial_quantity' => 10,
        'remaining_quantity' => 10,
        'sold_quantity' => 0,
        'status' => 'active',
    ]);
    $this->service = app(SalesOrderService::class);
    $this->createDraft = fn (?Customer $customer = null): SalesOrder => $this->service->create([
        'customer_id' => $customer?->id,
        'store_id' => $this->store->id,
        'cash_register_shift_id' => $this->shift->id,
        'items' => [[
            'product_variant_id' => $this->variant->id,
            'sale_unit_id' => null,
            'quantity' => 2,
            'unit_price' => 100,
        ]],
    ], $this->actor);
});

it('provisionally allocates FEFO stock during validation without deducting it', function (): void {
    $laterBatch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonths(2)->toDateString(),
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);
    $order = ($this->createDraft)();

    $this->service->validate($order, $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::VALIDATED)
        ->and($this->batch->refresh()->remaining_quantity)->toBe(10)
        ->and($order->items()->firstOrFail()->stockAllocations()->firstOrFail()->batch_id)->toBe($this->batch->id)
        ->and($laterBatch->refresh()->remaining_quantity)->toBe(10);
});

it('fulfills exact validated allocations and completes a previously paid order', function (): void {
    $order = ($this->createDraft)();
    $this->service->validate($order, $this->actor);
    $this->service->pay($order, [['payment_method' => 'cash', 'amount' => 200, 'reference' => null]], $this->actor);
    $this->service->fulfill($order, $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::COMPLETED)
        ->and($order->payment_status)->toBe(SalesOrderPaymentStatus::PAID)
        ->and($order->fulfilled_by)->toBe($this->actor->id)
        ->and($this->batch->refresh()->remaining_quantity)->toBe(8)
        ->and($this->batch->sold_quantity)->toBe(2);
});

it('records receivable charge and subsequent payment entries for an unpaid fulfilled order', function (): void {
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $this->service->fulfill($order, $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::FULFILLED)
        ->and((float) $order->receivableEntries()->where('type', 'charge')->sum('amount'))->toBe(200.0);

    $this->service->pay($order, [['payment_method' => 'transfer', 'amount' => 200, 'reference' => 'REF-1']], $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::COMPLETED)
        ->and((float) $order->receivableEntries()->where('type', 'payment')->sum('amount'))->toBe(200.0);
});

it('only cancels unpaid draft or validated orders with a reason', function (): void {
    $order = ($this->createDraft)();
    $this->service->validate($order, $this->actor);
    $this->service->cancel($order, 'Customer changed their mind.', $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::CANCELLED)
        ->and($order->cancellation_reason)->toBe('Customer changed their mind.')
        ->and($this->batch->refresh()->remaining_quantity)->toBe(10);
});
