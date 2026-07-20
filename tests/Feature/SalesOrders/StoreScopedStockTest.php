<?php

declare(strict_types=1);

use App\Enums\DiscountType;
use App\Enums\PermissionsEnum;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use App\Services\SalesOrderService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

beforeEach(function () {
    $this->storeA = Store::factory()->create(['name' => 'Store A']);
    $this->storeB = Store::factory()->create(['name' => 'Store B']);

    $this->actor = User::factory()->create();
    $this->actor->stores()->attach([$this->storeA->id, $this->storeB->id]);
    $this->actor->givePermissionTo(
        PermissionsEnum::SALES_MANAGE->value,
        PermissionsEnum::SALES_VIEW->value,
    );

    // A shared product variant with two batches: 5 units in storeA, 50 in storeB.
    // The variant's aggregate `stock` column is irrelevant to the store-scoped
    // search — stock comes from batches filtered by store_id.
    $product = Product::factory()->create();
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => 100,
        'stock' => 55, // aggregate across both stores (would be wrong to use)
    ]);

    Batch::factory()->create([
        'product_variant_id' => $this->variant->id,
        'store_id' => $this->storeA->id,
        'remaining_quantity' => 5,
        'initial_quantity' => 5,
        'status' => 'active',
    ]);

    Batch::factory()->create([
        'product_variant_id' => $this->variant->id,
        'store_id' => $this->storeB->id,
        'remaining_quantity' => 50,
        'initial_quantity' => 50,
        'status' => 'active',
    ]);
});

it('returns store-scoped stock when store_id is provided to the variant search endpoint', function () {
    $response = actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', [
            'filter' => $this->variant->product->name,
            'store_id' => $this->storeA->id,
        ]));

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and((int) $data[0]['stock'])->toBe(5);
});

it('returns store-B stock when scoped to store B', function () {
    $response = actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', [
            'filter' => $this->variant->product->name,
            'store_id' => $this->storeB->id,
        ]));

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and((int) $data[0]['stock'])->toBe(50);
});

it('returns aggregate stock when store_id is omitted (backward compat)', function () {
    $response = actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', [
            'filter' => $this->variant->product->name,
        ]));

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and((int) $data[0]['stock'])->toBe(55);
});

it('rejects creating a sales order without a store_id', function () {
    $payload = payloadFor(storeId: null);

    actingAs($this->actor)->postJson(route('sales-orders.store'), $payload);

    assertDatabaseCount('sales_orders', 0);
});

it('rejects creating a sales order with a store the user is not assigned to', function () {
    $unassignedStore = Store::factory()->create();
    $payload = payloadFor(storeId: $unassignedStore->id);

    actingAs($this->actor)->postJson(route('sales-orders.store'), $payload);

    assertDatabaseCount('sales_orders', 0);
});

it('persists the chosen store_id on the created order', function () {
    $payload = payloadFor(storeId: $this->storeB->id);

    actingAs($this->actor)->postJson(route('sales-orders.store'), $payload);

    $order = SalesOrder::latest('id')->firstOrFail();
    expect($order->store_id)->toBe($this->storeB->id);
});

it('deducts stock only from the selected store batches on a paid order', function () {
    // The HTTP store endpoint forces draft status (status is not a validated
    // field on StoreSalesOrderRequest), so exercise the service directly with
    // a paid order — the path that triggers FIFO deduction.
    $service = app(SalesOrderService::class);

    $order = $service->create([
        'store_id' => $this->storeA->id,
        'customer_id' => null,
        'discount_type' => DiscountType::FLAT->value,
        'discount_value' => 0,
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'sale_unit_id' => null,
                'quantity' => 3,
                'unit_price' => 100,
                'conversion_factor' => 1,
            ],
        ],
    ], $this->actor);
    $service->confirm($order, $this->actor);

    $storeABatch = Batch::where('product_variant_id', $this->variant->id)
        ->where('store_id', $this->storeA->id)
        ->sole();
    $storeBBatch = Batch::where('product_variant_id', $this->variant->id)
        ->where('store_id', $this->storeB->id)
        ->sole();

    // 5 - 3 = 2 in store A; store B untouched at 50.
    expect((int) $storeABatch->remaining_quantity)->toBe(2)
        ->and((int) $storeBBatch->remaining_quantity)->toBe(50);
});

// Helpers ---------------------------------------------------------------------

/**
 * Build a minimal valid sales-order payload for the current test variant.
 *
 * @return array<string, mixed>
 */
function payloadFor(?int $storeId, int $quantity = 1): array
{
    return [
        'store_id' => $storeId,
        'customer_id' => null,
        'discount_type' => DiscountType::FLAT->value,
        'discount_value' => 0,
        'notes' => null,
        'items' => [
            [
                'product_variant_id' => ProductVariant::latest('id')->firstOrFail()->id,
                'sale_unit_id' => null,
                'quantity' => $quantity,
                'unit_price' => 100,
                'conversion_factor' => 1,
            ],
        ],
        'payments' => [
            ['payment_method' => 'cash', 'amount' => 100 * $quantity, 'reference' => null],
        ],
    ];
}
