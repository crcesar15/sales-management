<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Http\Resources\Batches\BatchResource;
use App\Models\Batch;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReceptionOrderService;
use App\Services\SalesOrderService;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);
    $this->store = Store::factory()->create(['status' => 'active']);
    $this->admin->stores()->attach($this->store);
    $this->variant = ProductVariant::factory()->create(['product_id' => Product::factory()]);
});

it('freezes discounted purchase-pack costs across partial receipts', function () {
    $unit = ProductVariantUnit::query()->create(['product_variant_id' => $this->variant->id, 'type' => 'purchase',
        'name' => 'Case of 12', 'conversion_factor' => 12, 'status' => 'active']);
    $po = PurchaseOrder::factory()->create(['status' => 'approved', 'sub_total' => 144, 'discount' => 24, 'total' => 120]);
    $line = PurchaseOrderProduct::query()->create(['purchase_order_id' => $po->id, 'product_variant_id' => $this->variant->id,
        'unit_id' => $unit->id, 'quantity' => 2, 'price' => 72, 'total' => 144]);
    $service = app(ReceptionOrderService::class);
    for ($i = 0; $i < 2; $i++) {
        $receipt = $service->create(['purchase_order_id' => $po->id, 'store_id' => $this->store->id,
            'items' => [['purchase_order_item_id' => $line->id, 'batches' => [['purchase_unit_quantity' => 1, 'loose_unit_quantity' => 0]]]]], $this->admin);
        $service->complete($receipt, $this->admin);
    }
    $batches = Batch::where('reception_order_id', '!=', null)->get();
    expect($batches)->toHaveCount(2);
    foreach ($batches as $batch) {
        expect($batch->unit_cost)->toBe('5.000000000000')->and($batch->initial_quantity)->toBe(12);
    }
    $this->variant->update(['purchase_price' => 200]);
    $unit->update(['conversion_factor' => 24]);
    expect($batches->first()->fresh()->unit_cost)->toBe('5.000000000000');
});

it('carries cost and lineage through repeated transfers', function () {
    $source = Batch::factory()->create(['product_variant_id' => $this->variant->id, 'store_id' => $this->store->id,
        'unit_cost' => '6.25', 'remaining_quantity' => 10, 'status' => 'active', 'expiry_date' => null]);
    $from = $this->store;
    foreach (range(1, 2) as $iteration) {
        $to = Store::factory()->create();
        $transfer = StockTransfer::create(['from_store_id' => $from->id, 'to_store_id' => $to->id,
            'requested_by' => $this->admin->id, 'status' => 'received']);
        $transfer->items()->create(['product_variant_id' => $this->variant->id, 'quantity_requested' => 3,
            'quantity_sent' => 3, 'quantity_received' => 3]);
        app(StockTransferService::class)->completeTransfer($transfer, $this->admin);
        $destination = Batch::where('store_id', $to->id)->sole();
        expect($destination->unit_cost)->toBe('6.250000000000')
            ->and($destination->source_batch_id)->toBe($source->id);
        $source = $destination;
        $from = $to;
    }
});

it('snapshots the actual multi-batch costs and discounted revenue at fulfillment', function () {
    $shift = CashRegisterShift::factory()->create(['cash_register_id' => CashRegister::factory()->create(['store_id' => $this->store])->id,
        'user_id' => $this->admin->id, 'status' => 'open']);
    foreach ([[4, '6', 1], [6, '8', 2]] as [$quantity, $cost, $days]) {
        Batch::factory()->create(['product_variant_id' => $this->variant->id, 'store_id' => $this->store->id,
            'unit_cost' => $cost, 'remaining_quantity' => $quantity, 'status' => 'active', 'expiry_date' => today()->addDays($days)]);
    }
    $service = app(SalesOrderService::class);
    $order = $service->create(['customer_id' => Customer::factory()->create()->id, 'store_id' => $this->store->id,
        'cash_register_shift_id' => $shift->id, 'discount_type' => 'flat', 'discount_value' => 10,
        'items' => [['product_variant_id' => $this->variant->id, 'quantity' => 10, 'unit_price' => 10]]], $this->admin);
    $service->validate($order, $this->admin);
    $preview = $service->previewFulfillment($order->fresh(), $this->admin);
    $fulfilled = $service->fulfill($order->fresh(), $preview['token'], $this->admin);
    $item = $fulfilled->items->sole();
    expect($item->net_sales_amount)->toBe('90.00')
        ->and($item->stockAllocations->pluck('unit_cost')->all())->toBe(['6.000000000000', '8.000000000000']);
    $this->travel(1)->seconds();
    $snapshot = app(DashboardService::class)->snapshot($this->admin, []);
    expect($snapshot['sales']['profitability']['gross_profit'])->toBe(18.0);
    expect(fn () => $service->fulfill($fulfilled, $preview['token'], $this->admin))->toThrow(InvalidArgumentException::class);
    expect($item->stockAllocations()->count())->toBe(2);
    expect($item->stockAllocations->first()->toArray())->not->toHaveKey('unit_cost');
    expect(Activity::where('log_name', 'sales_order_stock_allocation')->get()->pluck('properties')->toJson())->not->toContain('unit_cost');
});

it('allows zero opening cost, keeps absent costs unknown and rejects unauthorized submitted costs', function () {
    $payload = ['store_id' => $this->store->id, 'product_variant_id' => $this->variant->id,
        'reason' => 'initial_stock', 'quantity_change' => 10];
    $this->actingAs($this->admin)->post(route('stock-adjustments.store'), [...$payload, 'unit_cost' => '0'])->assertRedirect();
    expect(Batch::where('store_id', $this->store->id)->sole()->unit_cost)->toBe('0.000000000000');
    $this->post(route('stock-adjustments.store'), $payload)->assertRedirect();
    expect(Batch::where('store_id', $this->store->id)->latest('id')->first()->unit_cost)->toBeNull();
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::STOCK_ADJUST);
    $this->actingAs($user)->postJson(route('stock-adjustments.store'), [...$payload, 'unit_cost' => 5])->assertUnprocessable();
});

it('requires both profitability permission and store scope to serialize historical costs', function () {
    $batch = Batch::factory()->create(['product_variant_id' => $this->variant->id,
        'store_id' => $this->store->id, 'unit_cost' => '6.25']);
    $reader = User::factory()->create();
    $request = Request::create('/');
    $request->setUserResolver(fn () => $reader);
    expect((new BatchResource($batch))->resolve($request))->not->toHaveKey('unit_cost');
    $reader->givePermissionTo(PermissionsEnum::DASHBOARD_PROFIT_VIEW);
    expect((new BatchResource($batch))->resolve($request))->not->toHaveKey('unit_cost');
    $reader->stores()->attach($this->store);
    $reader->unsetRelation('stores');
    expect((new BatchResource($batch))->resolve($request))->toHaveKey('unit_cost', '6.250000000000');
});
