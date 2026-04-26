<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReceptionOrder;
use App\Models\Store;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);

    $this->store = Store::factory()->create(['status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);
    $this->receptionOrder = ReceptionOrder::factory()->create();
});

function createBatch(ProductVariant $variant, Store $store, ReceptionOrder $receptionOrder, array $overrides = []): Batch
{
    return Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'reception_order_id' => $receptionOrder->id,
        ...$overrides,
    ]);
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

it('admin can view batch list', function () {
    actingAs($this->admin)
        ->get(route('batches'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Batches/Index')
            ->has('batches')
            ->has('filters')
            ->has('stores')
        );
});

it('admin can view batch detail', function () {
    $batch = createBatch($this->variant, $this->store, $this->receptionOrder);

    actingAs($this->admin)
        ->get(route('batches.show', $batch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Batches/Show/Index')
            ->has('batch')
        );
});

it('salesman is denied batch list', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('batches'))
        ->assertForbidden();
});

it('salesman is denied batch detail', function () {
    $batch = createBatch($this->variant, $this->store, $this->receptionOrder);
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('batches.show', $batch))
        ->assertForbidden();
});

it('guest is redirected from batch list', function () {
    get(route('batches'))
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| Batch List Filters
|--------------------------------------------------------------------------
*/

it('filters by status', function () {
    createBatch($this->variant, $this->store, $this->receptionOrder, ['status' => 'queued']);
    createBatch($this->variant, $this->store, $this->receptionOrder, ['status' => 'closed']);

    actingAs($this->admin)
        ->get(route('batches', ['status' => 'queued']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'queued')
            ->has('batches.data', 1)
        );
});

it('filters by store', function () {
    $otherStore = Store::factory()->create(['status' => 'active']);
    createBatch($this->variant, $this->store, $this->receptionOrder);
    createBatch($this->variant, $otherStore, $this->receptionOrder);

    actingAs($this->admin)
        ->get(route('batches', ['store_id' => $this->store->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('batches.data', 1)
        );
});

it('filters by product variant', function () {
    $otherVariant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);
    createBatch($this->variant, $this->store, $this->receptionOrder);
    createBatch($otherVariant, $this->store, $this->receptionOrder);

    actingAs($this->admin)
        ->get(route('batches', ['product_variant_id' => $this->variant->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('batches.data', 1)
        );
});

it('filters by expiring_soon', function () {
    createBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->addDays(10),
        'status' => 'active',
    ]);
    createBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => now()->addMonths(6),
        'status' => 'active',
    ]);

    actingAs($this->admin)
        ->get(route('batches', ['expiring_soon' => true]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('batches.data', 1)
        );
});

it('filters by expiry date range', function () {
    createBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => '2026-06-15',
    ]);
    createBatch($this->variant, $this->store, $this->receptionOrder, [
        'expiry_date' => '2026-12-15',
    ]);

    actingAs($this->admin)
        ->get(route('batches', [
            'expiry_from' => '2026-06-01',
            'expiry_to' => '2026-06-30',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('batches.data', 1)
        );
});

/*
|--------------------------------------------------------------------------
| Close Batch
|--------------------------------------------------------------------------
*/

it('admin with stock.adjust can close an active batch', function () {
    $batch = createBatch($this->variant, $this->store, $this->receptionOrder, ['status' => 'active']);

    actingAs($this->admin)
        ->patch(route('batches.close', $batch))
        ->assertRedirect();

    expect($batch->fresh()->status)->toBe('closed');
});

it('admin can close a queued batch', function () {
    $batch = createBatch($this->variant, $this->store, $this->receptionOrder, ['status' => 'queued']);

    actingAs($this->admin)
        ->patch(route('batches.close', $batch))
        ->assertRedirect();

    expect($batch->fresh()->status)->toBe('closed');
});

it('closing an already closed batch returns error', function () {
    $batch = createBatch($this->variant, $this->store, $this->receptionOrder, ['status' => 'closed']);

    actingAs($this->admin)
        ->patch(route('batches.close', $batch))
        ->assertSessionHasErrors();
});

it('close action is recorded in activity log', function () {
    $batch = createBatch($this->variant, $this->store, $this->receptionOrder, ['status' => 'active']);

    actingAs($this->admin)
        ->patch(route('batches.close', $batch), ['notes' => 'Audit close']);

    $batch->refresh();
    $closedActivity = $batch->activities->first(fn ($a) => $a->description === 'closed');
    expect($closedActivity)->not->toBeNull();
    expect($closedActivity->causer->id)->toBe($this->admin->id);
    expect($closedActivity->properties['notes'])->toBe('Audit close');
});

it('user without stock.adjust cannot close a batch', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $batch = createBatch($this->variant, $this->store, $this->receptionOrder, ['status' => 'active']);

    actingAs($salesman)
        ->patchJson(route('batches.close', $batch))
        ->assertForbidden();
});
