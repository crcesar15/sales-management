<?php

declare(strict_types=1);

use App\Enums\CashRegisterShiftStatus;
use App\Enums\PermissionsEnum;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Batch;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\CashRegisterShiftService;
use App\Services\SalesOrderService;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    $this->cashier = User::factory()->create();
    $this->cashier->stores()->attach($this->store);
    $this->cashier->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
    $this->register = CashRegister::factory()->create(['store_id' => $this->store]);
    $this->shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register,
        'user_id' => $this->cashier,
        'status' => CashRegisterShiftStatus::OPEN->value,
    ]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => Product::factory(),
        'price' => 100,
        'status' => 'active',
    ]);
    $this->batch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonth()->toDateString(),
        'initial_quantity' => 10,
        'remaining_quantity' => 10,
        'sold_quantity' => 0,
        'status' => 'active',
    ]);
    Setting::factory()->create([
        'key' => 'tax_rate',
        'value' => '0',
        'name' => 'Tax Rate (%)',
        'group' => 'tax',
    ]);
    $this->draftPayload = fn (): array => [
        'customer_id' => null,
        'is_walk_in' => true,
        'discount_type' => 'flat',
        'discount_value' => 0,
        'items' => [[
            'product_variant_id' => $this->variant->id,
            'sale_unit_id' => null,
            'quantity' => 2,
            'unit_price' => 1,
        ]],
    ];
    $this->handoverToken = fn (SalesOrder $order): string => app(SalesOrderService::class)
        ->previewPosFulfillment($order, $this->cashier)['token'];
});

it('creates a durable POS draft from the active shift and server prices', function (): void {
    Setting::set('tax_rate', 10);

    $response = $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());

    $order = SalesOrder::query()->sole();
    $response->assertRedirect(route('pos.sales.payment', $order));
    expect($order->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payment_status)->toBe(SalesOrderPaymentStatus::PENDING)
        ->and($order->store_id)->toBe($this->store->id)
        ->and($order->cash_register_shift_id)->toBe($this->shift->id)
        ->and((float) $order->sub_total)->toBe(200.0)
        ->and((float) $order->tax_amount)->toBe(20.0)
        ->and((float) $order->total)->toBe(220.0)
        ->and((float) $order->items()->firstOrFail()->unit_price)->toBe(100.0);
});

it('renders the payment page and rehydrates an editable draft', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->get(route('pos.sales.payment', $order))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pos/Payment/Index')
            ->where('order.id', $order->id)
            ->where('order.total', 200));

    $this->actingAs($this->cashier)
        ->get(route('pos.sales.edit', $order))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pos/Index')
            ->where('draft.id', $order->id)
            ->has('draft.items', 1));
});

it('discovers the current shift draft when reopening the POS', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->get(route('pos'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pos/Index')
            ->where('draft.id', $order->id));
});

it('updates the active shift draft instead of creating a duplicate', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $payload = ($this->draftPayload)();
    $payload['items'][0]['quantity'] = 3;

    $this->actingAs($this->cashier)->post(route('pos.sales.store'), $payload);

    $order = SalesOrder::query()->sole();
    expect(SalesOrder::query()->count())->toBe(1)
        ->and($order->items()->sole()->quantity)->toBe(3)
        ->and((float) $order->total)->toBe(300.0);
});

it('completes a cash sale and records tendered cash and change', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => ($this->handoverToken)($order),
            'payment_mode' => 'cash',
            'cash_received' => 250,
            'cash_amount' => null,
            'qr_reference' => null,
            'qr_confirmed' => false,
        ])
        ->assertRedirect(route('pos.sales.receipt', $order));

    $payment = $order->payments()->sole();
    $allocation = $order->items()->sole()->stockAllocations()->sole();
    expect($order->refresh()->status)->toBe(SalesOrderStatus::COMPLETED)
        ->and($order->payment_status)->toBe(SalesOrderPaymentStatus::PAID)
        ->and((float) $payment->amount)->toBe(200.0)
        ->and((float) $payment->tendered_amount)->toBe(250.0)
        ->and((float) $payment->change_amount)->toBe(50.0)
        ->and($payment->cash_register_shift_id)->toBe($this->shift->id)
        ->and($allocation->batch_id)->toBe($this->batch->id)
        ->and($allocation->quantity)->toBe(2)
        ->and($this->batch->refresh()->remaining_quantity)->toBe(8);
});

it('completes a manually confirmed QR sale without requiring a reference', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => ($this->handoverToken)($order),
            'payment_mode' => 'qr',
            'qr_confirmed' => true,
        ])
        ->assertRedirect(route('pos.sales.receipt', $order));

    $payment = $order->payments()->sole();
    expect($payment->payment_method->value)->toBe('qr')
        ->and((float) $payment->amount)->toBe(200.0)
        ->and($payment->reference)->toBeNull()
        ->and($payment->cash_register_shift_id)->toBeNull();
});

it('splits payment between cash and the automatically calculated QR remainder', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => ($this->handoverToken)($order),
            'payment_mode' => 'split',
            'cash_amount' => 80,
            'qr_reference' => 'QR-123',
            'qr_confirmed' => true,
        ])
        ->assertRedirect(route('pos.sales.receipt', $order));

    $payments = $order->payments()->orderBy('id')->get();
    expect($payments)->toHaveCount(2)
        ->and($payments[0]->payment_method->value)->toBe('cash')
        ->and((float) $payments[0]->amount)->toBe(80.0)
        ->and($payments[1]->payment_method->value)->toBe('qr')
        ->and((float) $payments[1]->amount)->toBe(120.0)
        ->and($payments[1]->reference)->toBe('QR-123');
});

it('rolls the full checkout back when stock is no longer available', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();
    $handoverToken = ($this->handoverToken)($order);
    $this->batch->update(['remaining_quantity' => 0]);

    $this->actingAs($this->cashier)
        ->from(route('pos.sales.payment', $order))
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => $handoverToken,
            'payment_mode' => 'cash',
            'cash_received' => 200,
        ])
        ->assertRedirect(route('pos.sales.payment', $order))
        ->assertSessionHasErrors('payment');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payment_status)->toBe(SalesOrderPaymentStatus::PENDING)
        ->and($order->payments()->exists())->toBeFalse();
});

it('does not complete a draft when its product becomes inactive', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();
    $handoverToken = ($this->handoverToken)($order);
    $this->variant->update(['status' => 'inactive']);

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => $handoverToken,
            'payment_mode' => 'cash',
            'cash_received' => 200,
        ])
        ->assertSessionHasErrors('payment');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payments()->exists())->toBeFalse();
});

it('prevents closing a shift with an active POS draft', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());

    expect(fn () => app(CashRegisterShiftService::class)->closeShift($this->shift, 0))
        ->toThrow(InvalidArgumentException::class, 'Complete or discard the active POS sale before closing this shift.');

    expect($this->shift->refresh()->status)->toBe(CashRegisterShiftStatus::OPEN);
});

it('prevents another cashier from accessing a POS draft', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();
    $otherCashier = User::factory()->create();
    $otherCashier->stores()->attach($this->store);
    $otherCashier->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
    CashRegisterShift::factory()->create([
        'cash_register_id' => CashRegister::factory()->create(['store_id' => $this->store]),
        'user_id' => $otherCashier,
        'status' => CashRegisterShiftStatus::OPEN->value,
    ]);

    $this->actingAs($otherCashier)
        ->get(route('pos.sales.payment', $order))
        ->assertRedirect(route('pos'));
});

it('requires explicit QR confirmation', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => ($this->handoverToken)($order),
            'payment_mode' => 'qr',
            'qr_confirmed' => false,
        ])
        ->assertSessionHasErrors('qr_confirmed');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payments()->exists())->toBeFalse();
});

it('does not duplicate payments when a completed checkout is submitted again', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();
    $payload = [
        'handover_token' => ($this->handoverToken)($order),
        'payment_mode' => 'cash',
        'cash_received' => 200,
    ];

    $this->actingAs($this->cashier)->post(route('pos.sales.complete', $order), $payload);
    $this->actingAs($this->cashier)
        ->from(route('pos.sales.receipt', $order))
        ->post(route('pos.sales.complete', $order), $payload)
        ->assertRedirect(route('pos.sales.receipt', $order))
        ->assertSessionHasErrors('payment');

    expect($order->payments()->count())->toBe(1);
});

it('requires a current handover preview before completing a sale', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'payment_mode' => 'cash',
            'cash_received' => 200,
        ])
        ->assertSessionHasErrors('handover_token');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payments()->exists())->toBeFalse();
});

it('rejects an expired handover preview without recording payment', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();
    $handoverToken = ($this->handoverToken)($order);
    $this->travel(11)->minutes();

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => $handoverToken,
            'payment_mode' => 'cash',
            'cash_received' => 200,
        ])
        ->assertSessionHasErrors('payment');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payments()->exists())->toBeFalse();
});

it('rejects a handover preview generated before the draft was changed', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();
    $handoverToken = ($this->handoverToken)($order);
    $updatedPayload = ($this->draftPayload)();
    $updatedPayload['items'][0]['quantity'] = 3;
    $this->actingAs($this->cashier)->put(route('pos.sales.update', $order), $updatedPayload);

    $this->actingAs($this->cashier)
        ->post(route('pos.sales.complete', $order), [
            'handover_token' => $handoverToken,
            'payment_mode' => 'cash',
            'cash_received' => 300,
        ])
        ->assertSessionHasErrors('payment');

    expect($order->refresh()->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payments()->exists())->toBeFalse();
});

it('discards an unpaid POS draft', function (): void {
    $this->actingAs($this->cashier)->post(route('pos.sales.store'), ($this->draftPayload)());
    $order = SalesOrder::query()->sole();

    $this->actingAs($this->cashier)
        ->delete(route('pos.sales.discard', $order))
        ->assertRedirect(route('pos'));

    expect($order->refresh()->status)->toBe(SalesOrderStatus::CANCELLED)
        ->and($order->cancellation_reason)->toBe('Discarded at POS checkout.');
});
