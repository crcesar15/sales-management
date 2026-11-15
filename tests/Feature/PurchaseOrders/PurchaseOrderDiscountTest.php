<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\Catalog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use App\Services\ReceptionOrderService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\put;
use function Pest\Laravel\putJson;

/** @return array{User, array<string, mixed>} */
function purchaseDiscountFixture(): array
{
    $actor = User::factory()->create();
    $actor->assignRole(RolesEnum::ADMIN);
    actingAs($actor);
    $vendor = Vendor::factory()->create(['status' => 'active']);
    $items = [];
    foreach ([[10, 20, 'percentage', 10], [5, 40, 'flat', 50]] as [$quantity, $price, $type, $value]) {
        $variant = ProductVariant::factory()->for(Product::factory())->create(['has_expiration' => false]);
        $offer = Catalog::factory()->create(['vendor_id' => $vendor->id, 'product_variant_id' => $variant->id, 'price' => $price, 'status' => 'active']);
        $items[] = ['product_variant_id' => $variant->id, 'catalog_id' => $offer->id, 'unit_id' => null, 'quantity' => $quantity, 'price' => $price, 'discount_type' => $type, 'discount_value' => $value];
    }

    return [$actor, ['vendor_id' => $vendor->id, 'order_date' => today()->toDateString(), 'discount_type' => 'percentage', 'discount_value' => 10, 'items' => $items]];
}

it('previews and saves both discounts using authoritative totals rather than client amounts', function () {
    [, $data] = purchaseDiscountFixture();
    postJson(route('api.v1.purchase-orders.preview'), $data)->assertSuccessful()
        ->assertJsonPath('data.gross_sub_total', '400.00')->assertJsonPath('data.line_discount_total', '70.00')
        ->assertJsonPath('data.sub_total', '330.00')->assertJsonPath('data.discount', '33.00')->assertJsonPath('data.total', '297.00')
        ->assertJsonPath('data.items.0.actual_base_unit_cost', 16.2)->assertJsonPath('data.items.1.actual_base_unit_cost', 27);
    expect(PurchaseOrder::count())->toBe(0);
    $data['total'] = 1;
    $data['sub_total'] = 1;
    $data['discount'] = 1;
    $data['items'][0]['discount_amount'] = 199;
    $data['items'][0]['total'] = 1;
    post(route('purchase-orders.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
    $order = PurchaseOrder::query()->sole();
    expect($order->total)->toBe('297.00')->and($order->sub_total)->toBe('330.00')->and($order->discount)->toBe('33.00')
        ->and($order->discount_type->value)->toBe('percentage')->and($order->discount_value)->toBe('10.00');
    expect($order->lineItems()->orderBy('id')->firstOrFail()->discount_amount)->toBe('20.00');
    getJson(route('api.v1.purchase-orders.show', $order))->assertSuccessful()->assertJsonPath('data.discount_value', 10)
        ->assertJsonPath('data.gross_sub_total', 400)->assertJsonPath('data.line_discount_total', 70);
    $actor = User::query()->whereKey($order->user_id)->sole();
    app(PurchaseOrderService::class)->approve($order, $actor);
    $store = Store::factory()->create(['status' => 'active']);
    $receiptItems = $order->lineItems()->orderBy('id')->get()->map(fn ($line): array => ['purchase_order_item_id' => $line->id, 'batches' => [['purchase_unit_quantity' => (int) $line->quantity, 'loose_unit_quantity' => 0]]])->all();
    $receipt = app(ReceptionOrderService::class)->create(['purchase_order_id' => $order->id, 'store_id' => $store->id, 'items' => $receiptItems], $actor);
    app(ReceptionOrderService::class)->complete($receipt, $actor);
    expect(Batch::query()->where('reception_order_id', $receipt->id)->orderBy('product_variant_id')->pluck('unit_cost')->all())->toBe(['16.200000000000', '27.000000000000']);
});

it('creates the same discounted order through the API and serializes discount details', function () {
    [, $data] = purchaseDiscountFixture();
    postJson(route('api.v1.purchase-orders.store'), $data)->assertCreated()
        ->assertJsonPath('data.total', 297)->assertJsonPath('data.gross_sub_total', 400)
        ->assertJsonPath('data.line_discount_total', 70)->assertJsonPath('data.line_items.0.discount_type', 'percentage')
        ->assertJsonPath('data.line_items.0.discount_amount', 20)->assertJsonPath('data.line_items.0.total', 180);
});

it('preserves percentage inputs and line discounts across header-only and item updates', function () {
    [$actor, $data] = purchaseDiscountFixture();
    $order = app(PurchaseOrderService::class)->create($data, $actor);
    put(route('purchase-orders.update', $order), ['discount_value' => 5])->assertSessionHasNoErrors();
    expect($order->refresh()->discount_type->value)->toBe('percentage')->and($order->discount)->toBe('16.50')->and($order->total)->toBe('313.50');
    $lines = $order->lineItems()->orderBy('id')->get();
    $items = $data['items'];
    foreach ($items as $index => &$item) {
        $item['id'] = ($lines->get($index) ?? throw new LogicException('Missing purchase-order fixture line.'))->id;
        unset($item['discount_type'], $item['discount_value']);
    }
    unset($item);
    $items[0]['quantity'] = 20;
    putJson(route('api.v1.purchase-orders.update', $order), ['items' => $items])->assertSuccessful()->assertJsonPath('data.total', 484.5);
    expect($order->refresh()->lineItems()->orderBy('id')->firstOrFail()->discount_amount)->toBe('40.00')
        ->and($order->discount_value)->toBe('5.00');
    put(route('purchase-orders.update', $order), ['notes' => 'Keep the same discounts'])->assertSessionHasNoErrors();
    expect($order->refresh()->total)->toBe('484.50');
});

it('validates discounts against net amounts consistently for web and API requests', function () {
    [, $data] = purchaseDiscountFixture();
    $invalidLine = $data;
    $invalidLine['items'][0]['discount_type'] = 'flat';
    $invalidLine['items'][0]['discount_value'] = 201;
    post(route('purchase-orders.store'), $invalidLine)->assertSessionHasErrors('items.0.discount_value');
    postJson(route('api.v1.purchase-orders.store'), $invalidLine)->assertUnprocessable()->assertJsonValidationErrors('items.0.discount_value');
    $invalidHeader = [...$data, 'discount_type' => 'flat', 'discount_value' => 331];
    post(route('purchase-orders.store'), $invalidHeader)->assertSessionHasErrors('discount_value');
    postJson(route('api.v1.purchase-orders.store'), $invalidHeader)->assertUnprocessable()->assertJsonValidationErrors('discount_value');
    $badPercentage = [...$data, 'discount_value' => 101];
    postJson(route('api.v1.purchase-orders.preview'), $badPercentage)->assertUnprocessable()->assertJsonValidationErrors('discount_value');
    $fractional = $data;
    $fractional['items'][0]['quantity'] = 0.5;
    postJson(route('api.v1.purchase-orders.store'), $fractional)->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
    $unordered = $data;
    $unordered['items'] = [5 => $data['items'][0], 2 => $data['items'][1]];
    postJson(route('api.v1.purchase-orders.preview'), $unordered)->assertUnprocessable()->assertJsonValidationErrors('items');
    expect(PurchaseOrder::count())->toBe(0);
});

it('validates header-only discounts against existing discounted lines before changing anything', function () {
    [$actor, $data] = purchaseDiscountFixture();
    $order = app(PurchaseOrderService::class)->create($data, $actor);
    put(route('purchase-orders.update', $order), ['discount_type' => 'flat', 'discount_value' => 331])->assertSessionHasErrors('discount_value');
    putJson(route('api.v1.purchase-orders.update', $order), ['discount_type' => 'flat', 'discount_value' => 331])->assertUnprocessable();
    expect($order->refresh()->total)->toBe('297.00')->and($order->lineItems()->count())->toBe(2);
});

it('spreads whole-line and PO discounts across purchasing units and repeated partial receipts', function () {
    [$actor, $data] = purchaseDiscountFixture();
    $variant = ProductVariant::query()->whereKey($data['items'][0]['product_variant_id'])->sole();
    $unit = ProductVariantUnit::create(['product_variant_id' => $variant->id, 'type' => 'purchase', 'name' => 'Case of 12', 'conversion_factor' => 12, 'status' => 'active']);
    Catalog::query()->whereKey($data['items'][0]['catalog_id'])->update(['unit_id' => $unit->id]);
    $item = [...$data['items'][0], 'unit_id' => $unit->id, 'quantity' => 2, 'price' => 72, 'discount_type' => 'flat', 'discount_value' => 24];
    $order = app(PurchaseOrderService::class)->create([...$data, 'items' => [$item], 'discount_type' => 'flat', 'discount_value' => 24], $actor);
    expect($order->total)->toBe('96.00');
    app(PurchaseOrderService::class)->approve($order, $actor);
    expect($variant->refresh()->replacement_cost)->toBe('6.000000000000');
    $store = Store::factory()->create(['status' => 'active']);
    $line = $order->lineItems()->sole();
    foreach ([1, 2] as $receiptNumber) {
        $receipt = app(ReceptionOrderService::class)->create(['purchase_order_id' => $order->id, 'store_id' => $store->id,
            'items' => [['purchase_order_item_id' => $line->id, 'batches' => [['purchase_unit_quantity' => 1, 'loose_unit_quantity' => 0]]]]], $actor);
        app(ReceptionOrderService::class)->complete($receipt, $actor);
        expect(Batch::query()->where('reception_order_id', $receipt->id)->sole()->unit_cost)->toBe('4.000000000000');
    }
    expect($variant->refresh()->replacement_cost)->toBe('6.000000000000');
});

it('keeps approved discounts immutable and allows genuinely free received stock', function () {
    [$actor, $data] = purchaseDiscountFixture();
    $data['items'] = [array_replace($data['items'][0], ['discount_type' => 'percentage', 'discount_value' => 100])];
    $order = app(PurchaseOrderService::class)->create($data, $actor);
    expect($order->total)->toBe('0.00');
    app(PurchaseOrderService::class)->approve($order, $actor);
    put(route('purchase-orders.update', $order), ['discount_value' => 0])->assertSessionHasErrors('status');
    putJson(route('api.v1.purchase-orders.update', $order), ['discount_value' => 0])->assertUnprocessable();
    $line = $order->lineItems()->sole();
    $store = Store::factory()->create(['status' => 'active']);
    $receipt = app(ReceptionOrderService::class)->create(['purchase_order_id' => $order->id, 'store_id' => $store->id,
        'items' => [['purchase_order_item_id' => $line->id, 'batches' => [['purchase_unit_quantity' => 10, 'loose_unit_quantity' => 0]]]]], $actor);
    app(ReceptionOrderService::class)->complete($receipt, $actor);
    expect(Batch::query()->where('reception_order_id', $receipt->id)->sole()->unit_cost)->toBe('0.000000000000');
});

it('requires purchasing mutation permissions for previews', function () {
    [, $data] = purchaseDiscountFixture();
    $reader = User::factory()->create();
    $reader->givePermissionTo(PermissionsEnum::PURCHASE_ORDERS_VIEW);
    actingAs($reader);
    postJson(route('api.v1.purchase-orders.preview'), $data)->assertForbidden();
    $reader->givePermissionTo(PermissionsEnum::PURCHASE_ORDERS_EDIT);
    postJson(route('api.v1.purchase-orders.preview'), $data)->assertSuccessful();
});

it('uses the same cent-remainder allocation for previews and actual receipts', function () {
    [$actor, $data] = purchaseDiscountFixture();
    $third = ProductVariant::factory()->for(Product::factory())->create(['has_expiration' => false]);
    $offer = Catalog::factory()->create(['vendor_id' => $data['vendor_id'], 'product_variant_id' => $third->id, 'price' => '0.01', 'status' => 'active']);
    $data['items'][] = ['product_variant_id' => $third->id, 'catalog_id' => $offer->id, 'quantity' => 1, 'price' => '0.01'];
    foreach ($data['items'] as &$item) {
        $item = [...$item, 'quantity' => 1, 'price' => '0.01', 'discount_type' => 'flat', 'discount_value' => 0];
    }
    unset($item);
    $data['discount_type'] = 'flat';
    $data['discount_value'] = '0.01';
    postJson(route('api.v1.purchase-orders.preview'), $data)->assertSuccessful()->assertJsonPath('data.items.0.final_total', '0.00')->assertJsonPath('data.items.1.final_total', '0.01')->assertJsonPath('data.items.2.final_total', '0.01');
    $order = app(PurchaseOrderService::class)->create($data, $actor);
    app(PurchaseOrderService::class)->approve($order, $actor);
    $items = $order->lineItems()->orderBy('id')->get()->map(fn ($line): array => ['purchase_order_item_id' => $line->id, 'batches' => [['purchase_unit_quantity' => 1, 'loose_unit_quantity' => 0]]])->all();
    $receipt = app(ReceptionOrderService::class)->create(['purchase_order_id' => $order->id, 'store_id' => Store::factory()->create()->id, 'items' => $items], $actor);
    app(ReceptionOrderService::class)->complete($receipt, $actor);
    expect(Batch::query()->where('reception_order_id', $receipt->id)->orderBy('product_variant_id')->pluck('unit_cost')->all())->toBe(['0.000000000000', '0.010000000000', '0.010000000000']);
});
