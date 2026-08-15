<?php

declare(strict_types=1);

use App\Enums\CashRegisterShiftStatus;
use App\Enums\PermissionsEnum;
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
    $this->actor->givePermissionTo(PermissionsEnum::SALES_MANAGE->value);
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

it('checks stock during validation without allocating or deducting it', function (): void {
    $laterBatch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonths(2)->toDateString(),
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);
    $order = ($this->createDraft)(Customer::factory()->create());

    $this->service->validate($order, $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::VALIDATED)
        ->and($this->batch->refresh()->remaining_quantity)->toBe(10)
        ->and($order->items()->firstOrFail()->stockAllocations)->toBeEmpty()
        ->and($laterBatch->refresh()->remaining_quantity)->toBe(10)
        ->and($preview['allocations'][0]['batch_id'])->toBe($this->batch->id);
});

it('checks the saved item quantity when a draft is updated before validation', function (): void {
    $order = ($this->createDraft)();

    $updatedOrder = $this->service->update($order, [
        'customer_id' => null,
        'discount_type' => 'flat',
        'discount_value' => 0,
        'notes' => null,
        'items' => [[
            'product_variant_id' => $this->variant->id,
            'sale_unit_id' => null,
            'quantity' => 3,
            'unit_price' => 100,
        ]],
    ], $this->actor);

    $validatedOrder = $this->service->validate($updatedOrder, $this->actor);

    expect($validatedOrder->items->firstOrFail()->quantity)->toBe(3)
        ->and($validatedOrder->items->firstOrFail()->stockAllocations)->toBeEmpty()
        ->and($this->batch->refresh()->remaining_quantity)->toBe(10);
});

it('requires selecting a customer or marking the sale as walk-in when updating a draft', function (): void {
    $order = ($this->createDraft)();

    $this->actingAs($this->actor)
        ->putJson(route('sales-orders.update', $order), [
            'customer_id' => null,
            'is_walk_in' => false,
            'discount_type' => 'flat',
            'discount_value' => 0,
            'notes' => null,
            'items' => [[
                'product_variant_id' => $this->variant->id,
                'sale_unit_id' => null,
                'quantity' => 2,
                'unit_price' => 100,
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id']);
});

it('persists an explicit walk-in customer selection when updating a draft', function (): void {
    $order = ($this->createDraft)(Customer::factory()->create());

    $updatedOrder = $this->service->update($order, [
        'customer_id' => null,
        'discount_type' => 'flat',
        'discount_value' => 0,
        'notes' => null,
        'items' => [[
            'product_variant_id' => $this->variant->id,
            'sale_unit_id' => null,
            'quantity' => 2,
            'unit_price' => 100,
        ]],
    ], $this->actor);

    expect($updatedOrder->customer_id)->toBeNull()
        ->and($order->refresh()->customer_id)->toBeNull();
});

it('reopens an unpaid validated order without affecting stock', function (): void {
    $order = ($this->createDraft)();
    $this->service->validate($order, $this->actor);

    $reopenedOrder = $this->service->reopen($order, $this->actor);

    expect($reopenedOrder->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($reopenedOrder->validated_at)->toBeNull()
        ->and($reopenedOrder->items)->toHaveCount(1)
        ->and($reopenedOrder->items->firstOrFail()->stockAllocations)->toBeEmpty()
        ->and($this->batch->refresh()->remaining_quantity)->toBe(10)
        ->and($this->batch->sold_quantity)->toBe(0);

    $revalidatedOrder = $this->service->validate($reopenedOrder, $this->actor);

    expect($revalidatedOrder->status)->toBe(SalesOrderStatus::VALIDATED)
        ->and($revalidatedOrder->items->firstOrFail()->stockAllocations)->toBeEmpty();
});

it('does not reopen validated orders with payments', function (): void {
    $order = ($this->createDraft)();
    $this->service->validate($order, $this->actor);
    $this->service->pay($order, [['payment_method' => 'cash', 'amount' => 50, 'reference' => null]], $this->actor);

    expect(fn (): SalesOrder => $this->service->reopen($order, $this->actor))
        ->toThrow(InvalidArgumentException::class, 'Orders with payments cannot be reopened for editing. Create a new sales order for additional products.');
});

it('only reopens validated orders', function (): void {
    $order = ($this->createDraft)();

    expect(fn (): SalesOrder => $this->service->reopen($order, $this->actor))
        ->toThrow(InvalidArgumentException::class, 'Only validated orders can be reopened for editing.');
});

it('creates FEFO allocations during fulfillment and completes a previously paid order', function (): void {
    $order = ($this->createDraft)();
    $this->service->validate($order, $this->actor);
    $this->service->pay($order, [['payment_method' => 'cash', 'amount' => 200, 'reference' => null]], $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);
    $this->service->fulfill($order, $preview['token'], $this->actor);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::COMPLETED)
        ->and($order->payment_status)->toBe(SalesOrderPaymentStatus::PAID)
        ->and($order->fulfilled_by)->toBe($this->actor->id)
        ->and($this->batch->refresh()->remaining_quantity)->toBe(8)
        ->and($this->batch->sold_quantity)->toBe(2)
        ->and($order->items()->firstOrFail()->stockAllocations()->firstOrFail()->batch_id)->toBe($this->batch->id)
        ->and($order->items()->firstOrFail()->stockAllocations()->sum('quantity'))->toBe(2);
});

it('uses the earliest-expiring available batch when fulfilling', function (): void {
    $laterBatch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonths(2)->toDateString(),
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);

    $this->service->fulfill($order, $preview['token'], $this->actor);

    expect($order->items()->firstOrFail()->stockAllocations()->firstOrFail()->batch_id)->toBe($this->batch->id)
        ->and($laterBatch->refresh()->remaining_quantity)->toBe(10);
});

it('does not generate a handover list for an unpaid walk-in order', function (): void {
    $order = ($this->createDraft)();
    $this->service->validate($order, $this->actor);

    expect(fn (): array => $this->service->previewFulfillment($order, $this->actor))
        ->toThrow(InvalidArgumentException::class, 'Walk-in orders must be paid before handover.');
});

it('does not fulfill an unpaid walk-in order with a handover list generated before it was made walk-in', function (): void {
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);
    $this->service->reopen($order, $this->actor);

    $updatedOrder = $this->service->update($order, [
        'customer_id' => null,
        'discount_type' => 'flat',
        'discount_value' => 0,
        'notes' => null,
        'items' => [[
            'product_variant_id' => $this->variant->id,
            'sale_unit_id' => null,
            'quantity' => 2,
            'unit_price' => 100,
        ]],
    ], $this->actor);
    $this->service->validate($updatedOrder, $this->actor);

    expect(fn (): SalesOrder => $this->service->fulfill($updatedOrder, $preview['token'], $this->actor))
        ->toThrow(InvalidArgumentException::class, 'Walk-in orders must be paid before handover.');

    expect($this->batch->refresh()->remaining_quantity)->toBe(10)
        ->and($updatedOrder->refresh()->status)->toBe(SalesOrderStatus::VALIDATED);
});

it('does not fulfill with a handover list whose stock changed after preview', function (): void {
    $laterBatch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonths(2)->toDateString(),
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);
    $this->batch->update(['remaining_quantity' => 0, 'sold_quantity' => 10, 'status' => 'closed']);

    expect(fn (): SalesOrder => $this->service->fulfill($order, $preview['token'], $this->actor))
        ->toThrow(InvalidArgumentException::class, 'The handover list is no longer available. Generate a new list.');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::VALIDATED)
        ->and($order->items()->firstOrFail()->stockAllocations)->toBeEmpty()
        ->and($laterBatch->refresh()->remaining_quantity)->toBe(10);
});

it('does not partially fulfill when current stock is insufficient', function (): void {
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);
    $this->batch->update(['remaining_quantity' => 1]);

    expect(fn (): SalesOrder => $this->service->fulfill($order, $preview['token'], $this->actor))
        ->toThrow(InvalidArgumentException::class, 'The handover list is no longer available. Generate a new list.');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::VALIDATED)
        ->and($order->items()->firstOrFail()->stockAllocations)->toBeEmpty()
        ->and($this->batch->refresh()->remaining_quantity)->toBe(1)
        ->and($this->batch->sold_quantity)->toBe(0);
});

it('rejects handover lists after five minutes', function (): void {
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);

    $this->travel(6)->minutes();

    try {
        expect(fn (): SalesOrder => $this->service->fulfill($order, $preview['token'], $this->actor))
            ->toThrow(InvalidArgumentException::class, 'The handover list is no longer available. Generate a new list.');
    } finally {
        $this->travelBack();
    }
});

it('replaces legacy provisional allocations with actual fulfillment allocations', function (): void {
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $item = $order->items()->firstOrFail();
    $item->stockAllocations()->create(['batch_id' => $this->batch->id, 'quantity' => 1]);
    $preview = $this->service->previewFulfillment($order, $this->actor);

    $this->service->fulfill($order, $preview['token'], $this->actor);

    expect($item->stockAllocations()->count())->toBe(1)
        ->and($item->stockAllocations()->firstOrFail()->quantity)->toBe(2);
});

it('records receivable charge and subsequent payment entries for an unpaid fulfilled order', function (): void {
    $order = ($this->createDraft)(Customer::factory()->create());
    $this->service->validate($order, $this->actor);
    $preview = $this->service->previewFulfillment($order, $this->actor);
    $this->service->fulfill($order, $preview['token'], $this->actor);

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
