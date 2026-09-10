<?php

declare(strict_types=1);

use App\Enums\CashRegisterShiftStatus;
use App\Enums\PermissionsEnum;
use App\Enums\SalesOrderStatus;
use App\Models\Batch;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use App\Services\SalesOrderService;

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    $this->cashier = User::factory()->create();
    $this->cashier->stores()->attach($this->store);
    $this->cashier->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
    $this->shift = CashRegisterShift::factory()->create([
        'cash_register_id' => CashRegister::factory()->create(['store_id' => $this->store]),
        'user_id' => $this->cashier,
        'status' => CashRegisterShiftStatus::OPEN->value,
    ]);
    $this->variant = ProductVariant::factory()->create(['product_id' => Product::factory()]);
    $this->expiredBatch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->subDay()->toDateString(),
        'remaining_quantity' => 50,
        'status' => 'active',
    ]);
    $this->firstBatch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addWeek()->toDateString(),
        'remaining_quantity' => 5,
        'status' => 'active',
    ]);
    $this->secondBatch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonth()->toDateString(),
        'remaining_quantity' => 5,
        'status' => 'active',
    ]);
});

it('previews POS fulfillment in FEFO order without changing inventory', function (): void {
    $order = createPosOrder($this->cashier, $this->variant, 7);

    $response = $this->actingAs($this->cashier, 'sanctum')
        ->postJson(route('api.v1.pos.sales.handover-preview', $order));

    $response->assertOk()
        ->assertJsonPath('data.allocations.0.batch_id', $this->firstBatch->id)
        ->assertJsonPath('data.allocations.0.quantity', 5)
        ->assertJsonPath('data.allocations.1.batch_id', $this->secondBatch->id)
        ->assertJsonPath('data.allocations.1.quantity', 2)
        ->assertJsonStructure(['data' => ['token', 'allocations' => [['product', 'variant', 'batch_identifier']]]]);

    expect($order->refresh()->status)->toBe(SalesOrderStatus::DRAFT)
        ->and($order->payments()->exists())->toBeFalse()
        ->and($order->items()->firstOrFail()->stockAllocations()->exists())->toBeFalse()
        ->and($this->expiredBatch->refresh()->remaining_quantity)->toBe(50)
        ->and($this->firstBatch->refresh()->remaining_quantity)->toBe(5);
});

it('requires POS access to generate a preview', function (): void {
    $order = createPosOrder($this->cashier, $this->variant, 1);
    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser, 'sanctum')
        ->postJson(route('api.v1.pos.sales.handover-preview', $order))
        ->assertForbidden();
});

it('requires authentication to generate a POS preview', function (): void {
    $order = createPosOrder($this->cashier, $this->variant, 1);

    $this->postJson(route('api.v1.pos.sales.handover-preview', $order))
        ->assertUnauthorized();
});

it('rejects a draft owned by another cashier and shift', function (): void {
    $order = createPosOrder($this->cashier, $this->variant, 1);
    $otherCashier = User::factory()->create();
    $otherCashier->stores()->attach($this->store);
    $otherCashier->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
    CashRegisterShift::factory()->create([
        'cash_register_id' => CashRegister::factory()->create(['store_id' => $this->store]),
        'user_id' => $otherCashier,
        'status' => CashRegisterShiftStatus::OPEN->value,
    ]);

    $this->actingAs($otherCashier, 'sanctum')
        ->postJson(route('api.v1.pos.sales.handover-preview', $order))
        ->assertUnprocessable();
});

it('tracks remaining stock across duplicate variant lines', function (): void {
    $order = createPosOrder($this->cashier, $this->variant, 6, true);

    $this->actingAs($this->cashier, 'sanctum')
        ->postJson(route('api.v1.pos.sales.handover-preview', $order))
        ->assertOk()
        ->assertJsonCount(3, 'data.allocations');
});

it('reports only fulfillable stock in POS product search', function (): void {
    $this->actingAs($this->cashier, 'sanctum')
        ->getJson(route('api.v1.variants.search', [
            'filter' => $this->variant->identifier,
            'store_id' => $this->store->id,
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.stock', 10);
});

function createPosOrder(User $cashier, ProductVariant $variant, int $quantity, bool $duplicateLines = false): SalesOrder
{
    $items = [[
        'product_variant_id' => $variant->id,
        'sale_unit_id' => null,
        'quantity' => $duplicateLines ? (int) ($quantity / 2) : $quantity,
    ]];
    if ($duplicateLines) {
        $items[] = $items[0];
    }

    return app(SalesOrderService::class)->createPosDraft([
        'customer_id' => null,
        'is_walk_in' => true,
        'discount_type' => 'flat',
        'discount_value' => 0,
        'items' => $items,
    ], $cashier);
}
