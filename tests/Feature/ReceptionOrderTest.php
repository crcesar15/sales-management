<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Catalog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\ReceptionOrder;
use App\Models\ReceptionOrderProduct;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;

use function Pest\Laravel\actingAs;

it('lists the purchase unit stored on the reception order purchase order item', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $unit = ProductVariantUnit::query()->create([
        'product_variant_id' => $variant->id,
        'type' => 'purchase',
        'name' => 'Case',
        'conversion_factor' => 12,
        'status' => 'active',
        'sort_order' => 1,
    ]);
    $catalog = Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
        'unit_id' => $unit->id,
    ]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'user_id' => $admin->id,
        'vendor_id' => $vendor->id,
        'status' => 'sent',
    ]);
    $purchaseOrderItem = PurchaseOrderProduct::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_variant_id' => $variant->id,
        'catalog_id' => $catalog->id,
        'unit_id' => $unit->id,
        'quantity' => 4,
        'price' => 10,
        'total' => 20,
    ]);
    $receptionOrder = ReceptionOrder::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'user_id' => $admin->id,
        'vendor_id' => $vendor->id,
        'store_id' => $store->id,
    ]);
    ReceptionOrderProduct::query()->create([
        'reception_order_id' => $receptionOrder->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'price' => 10,
        'total' => 20,
    ]);

    actingAs($admin)
        ->get(route('reception-orders'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ReceptionOrders/Index')
            ->where('receptionOrders.data.0.lineItems.0.purchase_unit.id', $unit->id)
            ->where('receptionOrders.data.0.lineItems.0.purchase_unit.name', 'Case')
            ->where('receptionOrders.data.0.lineItems.0.purchase_unit.conversion_factor', 12)
        );

    actingAs($admin)
        ->get(route('reception-orders.show', $receptionOrder))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ReceptionOrders/Show/Index')
            ->where('receptionOrder.line_items.0.purchase_order_item.unit.name', 'Case')
            ->where('receptionOrder.line_items.0.purchase_order_item.unit.conversion_factor', 12)
        );

    actingAs($admin)
        ->post(route('reception-orders.store'), [
            'purchase_order_id' => $purchaseOrder->id,
            'store_id' => $store->id,
            'items' => [[
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]],
        ])
        ->assertRedirect();

    expect(ReceptionOrder::query()->count())->toBe(2);
});
