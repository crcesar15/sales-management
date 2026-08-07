<?php

declare(strict_types=1);

use App\Enums\CashRegisterShiftStatus;
use App\Enums\PermissionsEnum;
use App\Models\Batch;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\SalesOrderService;

use function Pest\Laravel\actingAs;

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
    $this->variant = ProductVariant::factory()->create(['product_id' => Product::factory()]);
    $this->batch = Batch::factory()->create([
        'product_variant_id' => $this->variant,
        'store_id' => $this->store,
        'expiry_date' => now()->addMonth()->toDateString(),
        'remaining_quantity' => 5,
        'status' => 'active',
    ]);
    $this->service = app(SalesOrderService::class);
    $this->order = $this->service->create([
        'store_id' => $this->store->id,
        'cash_register_shift_id' => $this->shift->id,
        'items' => [[
            'product_variant_id' => $this->variant->id,
            'sale_unit_id' => null,
            'quantity' => 2,
            'unit_price' => 100,
        ]],
    ], $this->actor);
    $this->service->validate($this->order, $this->actor);
});

it('returns a temporary FEFO handover list without persisting allocations', function (): void {
    $response = actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.sales-orders.handover-preview', $this->order));

    $response->assertOk()
        ->assertJsonPath('data.allocations.0.batch_id', $this->batch->id)
        ->assertJsonPath('data.allocations.0.quantity', 2)
        ->assertJsonStructure(['data' => ['token', 'allocations' => [['variant', 'brand']]]]);

    expect($this->order->items()->firstOrFail()->stockAllocations)->toBeEmpty()
        ->and($this->batch->refresh()->remaining_quantity)->toBe(5);
});

it('requires sales-management permission to generate a handover list', function (): void {
    $unauthorizedUser = User::factory()->create();

    actingAs($unauthorizedUser, 'sanctum')
        ->getJson(route('api.v1.sales-orders.handover-preview', $this->order))
        ->assertForbidden();
});

it('requires authentication to generate a handover list', function (): void {
    $this->getJson(route('api.v1.sales-orders.handover-preview', $this->order))
        ->assertUnauthorized();
});
