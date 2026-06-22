<?php

declare(strict_types=1);

use App\Enums\DiscountType;
use App\Enums\SalesOrderStatus;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Store;
use App\Models\User;
use App\Services\FifoStockDeductionService;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->service = app(FifoStockDeductionService::class);

    $this->store = Store::factory()->create();
    $this->product = Product::factory()->create();
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'stock' => 10,
    ]);

    // Two FIFO batches: oldest first (created_at ordering).
    $this->batchA = Batch::factory()->create([
        'product_variant_id' => $this->variant->id,
        'store_id' => $this->store->id,
        'initial_quantity' => 6,
        'remaining_quantity' => 6,
        'sold_quantity' => 0,
        'transferred_quantity' => 0,
        'status' => 'active',
        'created_at' => now()->subDay(),
    ]);

    $this->batchB = Batch::factory()->create([
        'product_variant_id' => $this->variant->id,
        'store_id' => $this->store->id,
        'initial_quantity' => 4,
        'remaining_quantity' => 4,
        'sold_quantity' => 0,
        'transferred_quantity' => 0,
        'status' => 'active',
        'created_at' => now(),
    ]);
});

it('closes an exhausted batch when a FIFO sale deduction drains it to zero', function () {
    $user = User::factory()->create();

    $order = SalesOrder::factory()->create([
        'user_id' => $user->id,
        'store_id' => $this->store->id,
        'status' => SalesOrderStatus::PAID->value,
        'discount_type' => DiscountType::FLAT->value,
        'discount_value' => 0,
        'sub_total' => 600,
        'discount' => 0,
        'tax_amount' => 0,
        'total' => 600,
    ]);

    SalesOrderItem::factory()->create([
        'sales_order_id' => $order->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 6,
        'unit_price' => 100,
        'conversion_factor' => 1,
        'line_total' => 600,
    ]);

    DB::transaction(fn () => $this->service->deductForOrder($order->load('items')));

    // Batch A (oldest) is fully drained and auto-closed.
    expect($this->batchA->fresh()->remaining_quantity)->toBe(0)
        ->and($this->batchA->fresh()->sold_quantity)->toBe(6)
        ->and($this->batchA->fresh()->status)->toBe('closed');

    // Batch B is untouched.
    expect($this->batchB->fresh()->remaining_quantity)->toBe(4)
        ->and($this->batchB->fresh()->status)->toBe('active');

    // Variant stock recalculated from remaining active batches.
    expect($this->variant->fresh()->stock)->toBe(4);
});

it('throws InvalidArgumentException on insufficient stock for a sale deduction', function () {
    $user = User::factory()->create();

    $order = SalesOrder::factory()->create([
        'user_id' => $user->id,
        'store_id' => $this->store->id,
        'status' => SalesOrderStatus::PAID->value,
        'discount_type' => DiscountType::FLAT->value,
        'discount_value' => 0,
        'sub_total' => 1100,
        'discount' => 0,
        'tax_amount' => 0,
        'total' => 1100,
    ]);

    SalesOrderItem::factory()->create([
        'sales_order_id' => $order->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 11, // only 10 available
        'unit_price' => 100,
        'conversion_factor' => 1,
        'line_total' => 1100,
    ]);

    DB::transaction(fn () => $this->service->deductForOrder($order->load('items')));
})->throws(InvalidArgumentException::class, 'Insufficient stock');

it('closes an exhausted batch when a FIFO transfer deduction drains it to zero', function () {
    $this->service->deductForTransfer($this->variant->id, $this->store->id, 6);

    // Batch A (oldest) fully drained and closed; sold_quantity untouched, transferred incremented.
    expect($this->batchA->fresh()->remaining_quantity)->toBe(0)
        ->and($this->batchA->fresh()->transferred_quantity)->toBe(6)
        ->and($this->batchA->fresh()->sold_quantity)->toBe(0)
        ->and($this->batchA->fresh()->status)->toBe('closed');

    expect($this->batchB->fresh()->remaining_quantity)->toBe(4)
        ->and($this->batchB->fresh()->status)->toBe('active');

    expect($this->variant->fresh()->stock)->toBe(4);
});

it('throws InvalidArgumentException on insufficient stock for a transfer deduction', function () {
    $this->service->deductForTransfer($this->variant->id, $this->store->id, 11);
})->throws(InvalidArgumentException::class, 'Insufficient stock');