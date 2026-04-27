<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReceptionOrder;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Store;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);

    $this->storeA = Store::factory()->create(['status' => 'active']);
    $this->storeB = Store::factory()->create(['status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);
});

function createTransfer(Store $from, Store $to, User $requestedBy, array $overrides = []): StockTransfer
{
    $transfer = StockTransfer::create([
        'from_store_id' => $from->id,
        'to_store_id' => $to->id,
        'requested_by' => $requestedBy->id,
        'status' => 'requested',
        ...$overrides,
    ]);

    StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'product_variant_id' => ProductVariant::factory()->create([
            'product_id' => Product::factory()->create()->id,
        ])->id,
        'quantity_requested' => 10,
    ]);

    return $transfer;
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

it('admin can view transfer list', function () {
    actingAs($this->admin)
        ->get(route('stock-transfers'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StockTransfers/Index')
            ->has('transfers')
            ->has('filters')
            ->has('stores')
        );
});

it('admin can view transfer detail', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin);

    actingAs($this->admin)
        ->get(route('stock-transfers.show', $transfer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StockTransfers/Show/Index')
            ->has('transfer')
        );
});

it('admin can view create form', function () {
    actingAs($this->admin)
        ->get(route('stock-transfers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StockTransfers/Create/Index')
            ->has('stores')
        );
});

it('salesman without permission is denied transfer list', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('stock-transfers'))
        ->assertForbidden();
});

it('guest is redirected from transfer list', function () {
    get(route('stock-transfers'))
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Create Transfer
|--------------------------------------------------------------------------
*/

it('admin can create a transfer', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);

    actingAs($this->admin)
        ->post(route('stock-transfers.store'), [
            'from_store_id' => $this->storeA->id,
            'to_store_id' => $this->storeB->id,
            'notes' => 'Test transfer',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity_requested' => 5],
            ],
        ])
        ->assertRedirect(route('stock-transfers.show', StockTransfer::first()->id));

    $transfer = StockTransfer::first();
    expect($transfer->status)->toBe('requested');
    expect($transfer->from_store_id)->toBe($this->storeA->id);
    expect($transfer->to_store_id)->toBe($this->storeB->id);
    expect($transfer->items)->toHaveCount(1);
    expect($transfer->items->first()->quantity_requested)->toBe(5);
});

it('creating a transfer logs activity', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);

    actingAs($this->admin)
        ->post(route('stock-transfers.store'), [
            'from_store_id' => $this->storeA->id,
            'to_store_id' => $this->storeB->id,
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity_requested' => 5],
            ],
        ]);

    $transfer = StockTransfer::first();
    $activity = $transfer->activities->first(fn ($a) => $a->description === 'created');
    expect($activity)->not->toBeNull();
    expect($activity->causer->id)->toBe($this->admin->id);
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

it('rejects transfer with same source and destination store', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);

    actingAs($this->admin)
        ->post(route('stock-transfers.store'), [
            'from_store_id' => $this->storeA->id,
            'to_store_id' => $this->storeA->id,
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity_requested' => 5],
            ],
        ])
        ->assertSessionHasErrors(['from_store_id']);
});

it('rejects transfer without items', function () {
    actingAs($this->admin)
        ->post(route('stock-transfers.store'), [
            'from_store_id' => $this->storeA->id,
            'to_store_id' => $this->storeB->id,
            'items' => [],
        ])
        ->assertSessionHasErrors(['items']);
});

it('rejects transfer with quantity_requested <= 0', function () {
    $variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);

    actingAs($this->admin)
        ->post(route('stock-transfers.store'), [
            'from_store_id' => $this->storeA->id,
            'to_store_id' => $this->storeB->id,
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity_requested' => 0],
            ],
        ])
        ->assertSessionHasErrors(['items.0.quantity_requested']);
});

/*
|--------------------------------------------------------------------------
| Status Transitions
|--------------------------------------------------------------------------
*/

it('can transition from requested to picked', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin);

    actingAs($this->admin)
        ->patch(route('stock-transfers.update-status', $transfer), [
            'status' => 'picked',
            'items' => [
                ['id' => $transfer->items->first()->id, 'quantity_sent' => 10],
            ],
        ])
        ->assertRedirect();

    expect($transfer->fresh()->status)->toBe('picked');
    expect($transfer->items->first()->fresh()->quantity_sent)->toBe(10);
});

it('can transition from picked to in_transit', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin, ['status' => 'picked']);

    actingAs($this->admin)
        ->patch(route('stock-transfers.update-status', $transfer), [
            'status' => 'in_transit',
        ])
        ->assertRedirect();

    expect($transfer->fresh()->status)->toBe('in_transit');
});

it('can transition from in_transit to received', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin, ['status' => 'in_transit']);

    actingAs($this->admin)
        ->patch(route('stock-transfers.update-status', $transfer), [
            'status' => 'received',
            'items' => [
                ['id' => $transfer->items->first()->id, 'quantity_received' => 8],
            ],
        ])
        ->assertRedirect();

    expect($transfer->fresh()->status)->toBe('received');
    expect($transfer->items->first()->fresh()->quantity_received)->toBe(8);
});

it('rejects invalid status transition', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin);

    actingAs($this->admin)
        ->patch(route('stock-transfers.update-status', $transfer), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors(['status']);
});

it('status transition is logged in activity log', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin);

    actingAs($this->admin)
        ->patch(route('stock-transfers.update-status', $transfer), [
            'status' => 'picked',
        ]);

    $transfer->refresh();
    $activity = $transfer->activities->first(fn ($a) => $a->description === 'Status changed to picked');
    expect($activity)->not->toBeNull();
    expect($activity->causer->id)->toBe($this->admin->id);
});

/*
|--------------------------------------------------------------------------
| Complete Transfer (FIFO)
|--------------------------------------------------------------------------
*/

it('completing a transfer deducts source batches and creates destination batch', function () {
    $receptionOrder = ReceptionOrder::factory()->create();

    Batch::factory()->create([
        'product_variant_id' => $this->variant->id,
        'store_id' => $this->storeA->id,
        'reception_order_id' => $receptionOrder->id,
        'initial_quantity' => 20,
        'remaining_quantity' => 20,
        'sold_quantity' => 0,
        'transferred_quantity' => 0,
        'status' => 'active',
    ]);

    $transfer = StockTransfer::create([
        'from_store_id' => $this->storeA->id,
        'to_store_id' => $this->storeB->id,
        'requested_by' => $this->admin->id,
        'status' => 'received',
    ]);

    StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'product_variant_id' => $this->variant->id,
        'quantity_requested' => 10,
        'quantity_sent' => 10,
        'quantity_received' => 8,
    ]);

    actingAs($this->admin)
        ->patch(route('stock-transfers.update-status', $transfer), [
            'status' => 'completed',
        ])
        ->assertRedirect();

    $sourceBatch = Batch::where('store_id', $this->storeA->id)
        ->where('product_variant_id', $this->variant->id)
        ->first();

    expect($sourceBatch->fresh()->remaining_quantity)->toBe(12);
    expect($sourceBatch->fresh()->transferred_quantity)->toBe(8);

    $destBatch = Batch::where('store_id', $this->storeB->id)
        ->where('product_variant_id', $this->variant->id)
        ->first();

    expect($destBatch)->not->toBeNull();
    expect($destBatch->initial_quantity)->toBe(8);
    expect($destBatch->remaining_quantity)->toBe(8);
    expect($destBatch->reception_order_id)->toBeNull();
    expect($destBatch->status)->toBe('queued');

    expect($transfer->fresh()->status)->toBe('completed');
    expect($transfer->fresh()->completed_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Cancel Transfer
|--------------------------------------------------------------------------
*/

it('can cancel a requested transfer', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin);

    actingAs($this->admin)
        ->patch(route('stock-transfers.cancel', $transfer))
        ->assertRedirect();

    $transfer->refresh();
    expect($transfer->status)->toBe('cancelled');
    expect($transfer->cancelled_at)->not->toBeNull();
});

it('cannot cancel a completed transfer', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin, ['status' => 'completed']);

    actingAs($this->admin)
        ->patch(route('stock-transfers.cancel', $transfer))
        ->assertSessionHasErrors();
});

it('cancel is logged in activity log', function () {
    $transfer = createTransfer($this->storeA, $this->storeB, $this->admin);

    actingAs($this->admin)
        ->patch(route('stock-transfers.cancel', $transfer), ['reason' => 'Wrong store']);

    $transfer->refresh();
    $activity = $transfer->activities->first(fn ($a) => $a->description === 'cancelled');
    expect($activity)->not->toBeNull();
    expect($activity->properties['reason'])->toBe('Wrong store');
});

/*
|--------------------------------------------------------------------------
| List Filters
|--------------------------------------------------------------------------
*/

it('filters transfers by status', function () {
    createTransfer($this->storeA, $this->storeB, $this->admin, ['status' => 'requested']);
    createTransfer($this->storeA, $this->storeB, $this->admin, ['status' => 'completed']);

    actingAs($this->admin)
        ->get(route('stock-transfers', ['status' => 'requested']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'requested')
            ->has('transfers.data', 1)
        );
});
