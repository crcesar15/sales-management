<?php

declare(strict_types=1);

use App\Enums\CashRegisterShiftStatus;
use App\Enums\PermissionsEnum;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    $this->otherAssignedStore = Store::factory()->create();
    $this->unassignedStore = Store::factory()->create();
    $this->cashier = User::factory()->create();
    $this->cashier->stores()->attach([$this->store->id, $this->otherAssignedStore->id]);
    $this->cashier->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
    $this->otherCashier = User::factory()->create();
    $this->otherCashier->stores()->attach($this->store);
    $this->register = CashRegister::factory()->create(['store_id' => $this->store]);
    $this->shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register,
        'user_id' => $this->cashier,
        'status' => CashRegisterShiftStatus::OPEN->value,
    ]);
});

it('shows own sales and all drafts from the active shift store', function (): void {
    $ownSale = SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->store,
        'cash_register_shift_id' => $this->shift,
        'status' => SalesOrderStatus::COMPLETED->value,
        'payment_status' => SalesOrderPaymentStatus::PAID->value,
    ]);
    $sharedDraft = SalesOrder::factory()->create([
        'user_id' => $this->otherCashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::DRAFT->value,
    ]);
    SalesOrder::factory()->create([
        'user_id' => $this->otherCashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::COMPLETED->value,
    ]);
    SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->otherAssignedStore,
        'status' => SalesOrderStatus::COMPLETED->value,
    ]);
    SalesOrder::factory()->create([
        'user_id' => $this->otherCashier,
        'store_id' => $this->otherAssignedStore,
        'status' => SalesOrderStatus::DRAFT->value,
    ]);

    $expectedIds = collect([$ownSale->id, $sharedDraft->id])->sort()->values()->all();

    $this->actingAs($this->cashier)
        ->get(route('pos.sales.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pos/Sales/Index')
            ->where('currentShiftId', $this->shift->id)
            ->where('orders.data', fn ($orders): bool => $orders->pluck('id')->sort()->values()->all() === $expectedIds)
        );
});

it('shows only own sales from assigned stores without an open shift', function (): void {
    $this->shift->delete();

    $firstStoreSale = SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::COMPLETED->value,
    ]);
    $secondStoreDraft = SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->otherAssignedStore,
        'status' => SalesOrderStatus::DRAFT->value,
    ]);
    SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->unassignedStore,
        'status' => SalesOrderStatus::COMPLETED->value,
    ]);
    SalesOrder::factory()->create([
        'user_id' => $this->otherCashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::DRAFT->value,
    ]);

    $expectedIds = collect([$firstStoreSale->id, $secondStoreDraft->id])->sort()->values()->all();

    $this->actingAs($this->cashier)
        ->get(route('pos.sales.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pos/Sales/Index')
            ->where('currentShiftId', null)
            ->where('orders.data', fn ($orders): bool => $orders->pluck('id')->sort()->values()->all() === $expectedIds)
        );
});

it('applies search status and date filters to visible sales', function (): void {
    $matchingOrder = SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::COMPLETED->value,
        'created_at' => now(),
    ]);
    SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::DRAFT->value,
        'created_at' => now(),
    ]);
    SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::COMPLETED->value,
        'created_at' => now()->subDays(2),
    ]);

    $this->actingAs($this->cashier)
        ->get(route('pos.sales.index', [
            'search' => (string) $matchingOrder->id,
            'status' => SalesOrderStatus::COMPLETED->value,
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $matchingOrder->id)
        );
});

it('allows read-only access to visible sales and shared drafts', function (): void {
    $ownSale = SalesOrder::factory()->create([
        'user_id' => $this->cashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::COMPLETED->value,
    ]);
    $sharedDraft = SalesOrder::factory()->create([
        'user_id' => $this->otherCashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::DRAFT->value,
    ]);

    foreach ([$ownSale, $sharedDraft] as $order) {
        $this->actingAs($this->cashier)
            ->get(route('pos.sales.show', $order))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Pos/Sales/Show/Index')
                ->where('order.id', $order->id)
            );
    }
});

it('does not expose orders outside the POS visibility scope', function (): void {
    $otherCashierSale = SalesOrder::factory()->create([
        'user_id' => $this->otherCashier,
        'store_id' => $this->store,
        'status' => SalesOrderStatus::COMPLETED->value,
    ]);
    $otherStoreDraft = SalesOrder::factory()->create([
        'user_id' => $this->otherCashier,
        'store_id' => $this->otherAssignedStore,
        'status' => SalesOrderStatus::DRAFT->value,
    ]);

    $this->actingAs($this->cashier)->get(route('pos.sales.show', $otherCashierSale))->assertNotFound();
    $this->actingAs($this->cashier)->get(route('pos.sales.show', $otherStoreDraft))->assertNotFound();
});

it('requires POS access permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('pos.sales.index'))->assertForbidden();
});

it('redirects guests to login', function (): void {
    $this->get(route('pos.sales.index'))->assertRedirect(route('login'));
});
