<?php

declare(strict_types=1);

use App\Enums\AdjustmentReason;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Models\User;
use App\Services\StockAdjustmentService;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);

    $this->store = Store::factory()->create(['status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createActiveBatch(ProductVariant $variant, Store $store, int $remaining = 50, array $overrides = []): Batch
{
    return Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'remaining_quantity' => $remaining,
        'initial_quantity' => $remaining,
        'status' => 'active',
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function adjustmentPayload(ProductVariant $variant, Store $store, array $overrides = []): array
{
    return [
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'quantity_change' => 10,
        'reason' => AdjustmentReason::PHYSICAL_AUDIT->value,
        'notes' => 'Test adjustment',
        ...$overrides,
    ];
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

it('admin can view adjustments list', function () {
    actingAs($this->admin)
        ->get(route('stock-adjustments'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StockAdjustments/Index')
            ->has('adjustments')
            ->has('filters')
            ->has('stores')
        );
});

it('admin can view create adjustment form', function () {
    actingAs($this->admin)
        ->get(route('stock-adjustments.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StockAdjustments/Create/Index')
            ->has('stores')
            ->has('stores.0.opening_inventory_finalized_at')
            ->has('reasons')
        );
});

it('admin can view adjustment detail', function () {
    $batch = createActiveBatch($this->variant, $this->store);
    $service = app(StockAdjustmentService::class);
    $adjustment = $service->apply(
        adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch->id]),
        $this->admin,
    );

    actingAs($this->admin)
        ->get(route('stock-adjustments.show', $adjustment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('StockAdjustments/Show/Index')
            ->has('adjustment')
        );
});

it('salesman without stock.adjust permission is denied from adjustments list', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('stock-adjustments'))
        ->assertForbidden();
});

it('salesman without stock.adjust permission is denied from creating adjustment', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('stock-adjustments.create'))
        ->assertForbidden();
});

it('guest is redirected from adjustments list', function () {
    get(route('stock-adjustments'))
        ->assertRedirect(route('login'));
});

it('admin sees all users adjustments', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(RolesEnum::ADMIN);

    $batch1 = createActiveBatch($this->variant, $this->store, 100);
    $batch2 = createActiveBatch($this->variant, $this->store, 100);

    $service = app(StockAdjustmentService::class);
    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch1->id]), $this->admin);
    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch2->id]), $otherAdmin);

    actingAs($this->admin)
        ->get(route('stock-adjustments'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 2)
        );
});

it('salesman with stock.adjust sees only own adjustments', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $salesman->givePermissionTo(PermissionsEnum::STOCK_ADJUST->value);

    $batch1 = createActiveBatch($this->variant, $this->store, 100);
    $batch2 = createActiveBatch($this->variant, $this->store, 100);

    $service = app(StockAdjustmentService::class);
    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch1->id]), $salesman);
    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch2->id]), $this->admin);

    actingAs($salesman)
        ->get(route('stock-adjustments'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 1)
            ->where('adjustments.data.0.user.id', $salesman->id)
        );
});

it('salesman with stock.adjust can view own adjustment detail', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $salesman->givePermissionTo(PermissionsEnum::STOCK_ADJUST->value);

    $batch = createActiveBatch($this->variant, $this->store);
    $service = app(StockAdjustmentService::class);
    $adjustment = $service->apply(
        adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch->id]),
        $salesman,
    );

    actingAs($salesman)
        ->get(route('stock-adjustments.show', $adjustment))
        ->assertOk();
});

it('salesman with stock.adjust cannot view another users adjustment detail', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $salesman->givePermissionTo(PermissionsEnum::STOCK_ADJUST->value);

    $batch = createActiveBatch($this->variant, $this->store);
    $service = app(StockAdjustmentService::class);
    $adjustment = $service->apply(
        adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch->id]),
        $this->admin,
    );

    actingAs($salesman)
        ->getJson(route('stock-adjustments.show', $adjustment))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

it('rejects adjustment without reason', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, ['reason' => '']))
        ->assertSessionHasErrors(['reason']);
});

it('rejects adjustment with invalid reason value', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, ['reason' => 'invalid_reason']))
        ->assertSessionHasErrors(['reason']);
});

it('rejects adjustment with quantity_change of zero', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, ['quantity_change' => 0]))
        ->assertSessionHasErrors(['quantity_change']);
});

it('rejects adjustment without product_variant_id', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, ['product_variant_id' => null]))
        ->assertSessionHasErrors(['product_variant_id']);
});

it('rejects adjustment without store_id', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, ['store_id' => null]))
        ->assertSessionHasErrors(['store_id']);
});

it('rejects adjustment with non-existent batch_id', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, ['batch_id' => 999999]))
        ->assertSessionHasErrors(['batch_id']);
});

it('rejects adjustment with notes exceeding 1000 characters', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, ['notes' => str_repeat('a', 1001)]))
        ->assertSessionHasErrors(['notes']);
});

/*
|--------------------------------------------------------------------------
| Adjustment Creation — Positive Delta
|--------------------------------------------------------------------------
*/

it('positive adjustment increases batch remaining_quantity', function () {
    $batch = createActiveBatch($this->variant, $this->store, 50);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => 10,
            'batch_id' => $batch->id,
        ]))
        ->assertRedirect(route('stock-adjustments.show', StockAdjustment::first()?->id));

    expect($batch->fresh()?->remaining_quantity)->toBe(60);
});

it('creates adjustment record with correct attributes', function () {
    $batch = createActiveBatch($this->variant, $this->store, 30);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => 10,
            'batch_id' => $batch->id,
            'reason' => AdjustmentReason::CORRECTION->value,
            'notes' => 'Inventory correction',
        ]));

    $adjustment = StockAdjustment::first();
    expect($adjustment)->not->toBeNull();
    expect($adjustment?->product_variant_id)->toBe($this->variant->id);
    expect($adjustment?->store_id)->toBe($this->store->id);
    expect($adjustment?->user_id)->toBe($this->admin->id);
    expect($adjustment?->batch_id)->toBe($batch->id);
    expect($adjustment?->quantity_change)->toBe(10);
    expect($adjustment?->reason?->value)->toBe(AdjustmentReason::CORRECTION->value);
    expect($adjustment?->notes)->toBe('Inventory correction');
});

it('logs activity when creating adjustment', function () {
    $batch = createActiveBatch($this->variant, $this->store, 50);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'batch_id' => $batch->id,
        ]));

    $adjustment = StockAdjustment::first();
    /** @var Illuminate\Support\Collection<int, Activity> $activities */
    $activities = $adjustment?->activities;

    /** @var Activity|null $activity */
    $activity = $activities->last(fn ($a) => $a->description === 'created');

    expect($activity)->not->toBeNull();
    expect($activity?->causer_id)->toBe($this->admin->id);
    expect($activity?->properties['delta'] ?? 0)->toBe(10);
    expect($activity?->properties['batch_id'] ?? 0)->toBe($batch->id);
});

/*
|--------------------------------------------------------------------------
| Adjustment Creation — Negative Delta
|--------------------------------------------------------------------------
*/

it('negative adjustment decreases batch remaining_quantity', function () {
    $batch = createActiveBatch($this->variant, $this->store, 50);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => -20,
            'batch_id' => $batch->id,
        ]))
        ->assertRedirect();

    expect($batch->fresh()?->remaining_quantity)->toBe(30);
    expect(StockAdjustment::first()?->quantity_change)->toBe(-20);
});

it('closes batch when remaining_quantity reaches zero', function () {
    $batch = createActiveBatch($this->variant, $this->store, 25);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => -25,
            'batch_id' => $batch->id,
        ]));

    $batch = $batch->fresh();
    expect($batch?->remaining_quantity)->toBe(0);
    expect($batch?->status)->toBe('closed');
});

it('returns error when negative adjustment exceeds available stock', function () {
    $batch = createActiveBatch($this->variant, $this->store, 10);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => -20,
            'batch_id' => $batch->id,
        ]))
        ->assertSessionHasErrors(['quantity_change']);

    expect(StockAdjustment::count())->toBe(0);
    expect($batch->fresh()?->remaining_quantity)->toBe(10);
});

/*
|--------------------------------------------------------------------------
| Batch Selection and Opening Inventory
|--------------------------------------------------------------------------
*/

it('requires an explicit batch for non-initial adjustments', function () {
    $olderBatch = createActiveBatch($this->variant, $this->store, 30, ['created_at' => now()->subDays(2)]);
    $newerBatch = createActiveBatch($this->variant, $this->store, 50, ['created_at' => now()->subDay()]);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => 5,
        ]))
        ->assertSessionHasErrors(['batch_id']);

    expect(StockAdjustment::count())->toBe(0);
    expect($olderBatch->fresh()?->remaining_quantity)->toBe(30);
    expect($newerBatch->fresh()?->remaining_quantity)->toBe(50);
});

it('rejects an unavailable selected batch', function () {
    $batch = createActiveBatch($this->variant, $this->store, 10);
    $batch->update(['status' => 'closed']);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => -5,
            'batch_id' => $batch->id,
        ]))
        ->assertSessionHasErrors(['batch_id']);
});

it('creates an opening batch for initial stock', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => 15,
            'reason' => AdjustmentReason::INITIAL_STOCK->value,
            'batch_id' => null,
        ]))
        ->assertRedirect();

    $batch = Batch::where('product_variant_id', $this->variant->id)
        ->where('store_id', $this->store->id)
        ->first();

    expect($batch)->not->toBeNull();
    expect($batch?->initial_quantity)->toBe(15);
    expect($batch?->remaining_quantity)->toBe(15);
    expect($batch?->reception_order_id)->toBeNull();
    expect($batch?->status)->toBe('active');
    expect(StockAdjustment::first()?->batch_id)->toBe($batch?->id);
});

it('allows multiple opening batches before opening inventory is finalized', function () {
    $firstPayload = adjustmentPayload($this->variant, $this->store, [
        'quantity_change' => 15,
        'reason' => AdjustmentReason::INITIAL_STOCK->value,
        'batch_id' => null,
        'batch_identifier' => 'OPENING-A',
    ]);
    $secondPayload = adjustmentPayload($this->variant, $this->store, [
        'quantity_change' => 25,
        'reason' => AdjustmentReason::INITIAL_STOCK->value,
        'batch_id' => null,
        'batch_identifier' => 'OPENING-B',
    ]);

    actingAs($this->admin)->post(route('stock-adjustments.store'), $firstPayload)->assertRedirect();
    actingAs($this->admin)->post(route('stock-adjustments.store'), $secondPayload)->assertRedirect();

    expect(Batch::query()->where('product_variant_id', $this->variant->id)->where('store_id', $this->store->id)->count())->toBe(2);
    expect(StockAdjustment::query()->where('reason', AdjustmentReason::INITIAL_STOCK->value)->count())->toBe(2);
});

it('rejects a non-positive initial stock quantity', function () {
    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => -1,
            'reason' => AdjustmentReason::INITIAL_STOCK->value,
            'batch_id' => null,
        ]))
        ->assertSessionHasErrors(['quantity_change']);
});

it('rejects a positive loss adjustment', function () {
    $batch = createActiveBatch($this->variant, $this->store, 10);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => 1,
            'reason' => AdjustmentReason::DAMAGE->value,
            'batch_id' => $batch->id,
        ]))
        ->assertSessionHasErrors(['quantity_change']);
});

it('finalizes opening inventory and prevents further initial stock adjustments', function () {
    actingAs($this->admin)
        ->from(route('stock-adjustments.create'))
        ->patch(route('stock-adjustments.finalize-opening-inventory', $this->store))
        ->assertRedirect(route('stock-adjustments.create'));

    expect($this->store->fresh()?->opening_inventory_finalized_at)->not->toBeNull();

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => 15,
            'reason' => AdjustmentReason::INITIAL_STOCK->value,
            'batch_id' => null,
        ]))
        ->assertSessionHasErrors(['reason']);

    expect(Batch::count())->toBe(0);
});

it('returns expired active batches for stock adjustment selection', function () {
    $batch = createActiveBatch($this->variant, $this->store, 10, ['expiry_date' => now()->subDay()]);

    actingAs($this->admin)
        ->getJson(route('api.v1.batches.available', [
            'product_variant_id' => $this->variant->id,
            'store_id' => $this->store->id,
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.id', $batch->id);
});

it('keeps batch active when adjustment is applied', function () {
    $batch = createActiveBatch($this->variant, $this->store, 40);

    actingAs($this->admin)
        ->post(route('stock-adjustments.store'), adjustmentPayload($this->variant, $this->store, [
            'quantity_change' => 5,
            'batch_id' => $batch->id,
        ]));

    $batch = $batch->fresh();
    expect($batch?->status)->toBe('active');
    expect($batch?->remaining_quantity)->toBe(45);
});

/*
|--------------------------------------------------------------------------
| List Filters
|--------------------------------------------------------------------------
*/

it('filters adjustments by store_id', function () {
    $store2 = Store::factory()->create(['status' => 'active']);
    $batch1 = createActiveBatch($this->variant, $this->store, 100);
    $batch2 = createActiveBatch($this->variant, $store2, 100);

    $service = app(StockAdjustmentService::class);
    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch1->id]), $this->admin);
    $service->apply(adjustmentPayload($this->variant, $store2, ['batch_id' => $batch2->id]), $this->admin);

    actingAs($this->admin)
        ->get(route('stock-adjustments', ['store_id' => $this->store->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 1)
            ->where('adjustments.data.0.store_id', $this->store->id)
        );
});

it('filters adjustments by reason', function () {
    $batch1 = createActiveBatch($this->variant, $this->store, 100);
    $batch2 = createActiveBatch($this->variant, $this->store, 100);

    $service = app(StockAdjustmentService::class);
    $service->apply(adjustmentPayload($this->variant, $this->store, [
        'batch_id' => $batch1->id,
        'reason' => AdjustmentReason::DAMAGE->value,
        'quantity_change' => -10,
    ]), $this->admin);
    $service->apply(adjustmentPayload($this->variant, $this->store, [
        'batch_id' => $batch2->id,
        'reason' => AdjustmentReason::CORRECTION->value,
    ]), $this->admin);

    actingAs($this->admin)
        ->get(route('stock-adjustments', ['reason' => AdjustmentReason::DAMAGE->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 1)
        );
});

it('filters adjustments by date_from', function () {
    $batch = createActiveBatch($this->variant, $this->store, 100);

    $service = app(StockAdjustmentService::class);
    $adjustment = $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch->id]), $this->admin);

    StockAdjustment::where('id', $adjustment->id)->update(['created_at' => now()->subDays(5)]);

    $batch2 = createActiveBatch($this->variant, $this->store, 100);
    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch2->id]), $this->admin);

    actingAs($this->admin)
        ->get(route('stock-adjustments', ['date_from' => now()->subDay()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 1)
        );
});

it('filters adjustments by date_to', function () {
    $batch = createActiveBatch($this->variant, $this->store, 100);

    $service = app(StockAdjustmentService::class);
    $adjustment = $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch->id]), $this->admin);

    StockAdjustment::where('id', $adjustment->id)->update(['created_at' => now()->subDays(5)]);

    $batch2 = createActiveBatch($this->variant, $this->store, 100);
    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch2->id]), $this->admin);

    actingAs($this->admin)
        ->get(route('stock-adjustments', ['date_to' => now()->subDays(3)->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 1)
        );
});

it('includes adjustments created later on the selected end date', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create()->id,
    ]);
    $batch = createActiveBatch($variant, $store, 100);

    $service = app(StockAdjustmentService::class);
    $adjustment = $service->apply(adjustmentPayload($variant, $store, ['batch_id' => $batch->id]), $admin);

    StockAdjustment::where('id', $adjustment->id)->update(['created_at' => now()->endOfDay()]);

    actingAs($admin)
        ->get(route('stock-adjustments', ['date_to' => now()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 1)
        );
});

it('filters adjustments by date range', function () {
    $batch1 = createActiveBatch($this->variant, $this->store, 100);
    $batch2 = createActiveBatch($this->variant, $this->store, 100);
    $batch3 = createActiveBatch($this->variant, $this->store, 100);

    $service = app(StockAdjustmentService::class);

    $adj1 = $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch1->id]), $this->admin);
    StockAdjustment::where('id', $adj1->id)->update(['created_at' => now()->subDays(10)]);

    $adj2 = $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch2->id]), $this->admin);
    StockAdjustment::where('id', $adj2->id)->update(['created_at' => now()->subDays(3)]);

    $service->apply(adjustmentPayload($this->variant, $this->store, ['batch_id' => $batch3->id]), $this->admin);

    actingAs($this->admin)
        ->get(route('stock-adjustments', [
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('adjustments.meta.total', 1)
        );
});
