<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Catalog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\User;
use App\Models\Vendor;

use function Pest\Laravel\actingAs;

/**
 * @return array{
 *     vendor_id: int,
 *     order_date: string,
 *     items: array<int, array{catalog_id: int, product_variant_id: int, unit_id: int|null, quantity: int|float, price: int}>
 * }
 */
function purchaseOrderItemPayload(Catalog $catalog, ProductVariant $variant, ?int $unitId = null, int|float $quantity = 1): array
{
    return [
        'vendor_id' => $catalog->vendor_id,
        'order_date' => now()->toDateString(),
        'items' => [[
            'catalog_id' => $catalog->id,
            'product_variant_id' => $variant->id,
            'unit_id' => $unitId,
            'quantity' => $quantity,
            'price' => 75,
        ]],
    ];
}

/**
 * @return array{admin: User, vendor: Vendor, variant: ProductVariant, purchaseUnit: ProductVariantUnit, catalog: Catalog}
 */
function purchaseOrderValidationContext(): array
{
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);
    $vendor = Vendor::factory()->create();
    $variant = ProductVariant::factory()->for(Product::factory())->create();
    $purchaseUnit = ProductVariantUnit::query()->create([
        'product_variant_id' => $variant->id,
        'type' => 'purchase',
        'name' => 'Box',
        'conversion_factor' => 30,
        'status' => 'active',
        'sort_order' => 1,
    ]);
    $catalog = Catalog::factory()->create([
        'vendor_id' => $vendor->id,
        'product_variant_id' => $variant->id,
        'unit_id' => $purchaseUnit->id,
        'minimum_order_quantity' => 2,
        'status' => 'active',
    ]);

    return [
        'admin' => $admin,
        'vendor' => $vendor,
        'variant' => $variant,
        'purchaseUnit' => $purchaseUnit,
        'catalog' => $catalog,
    ];
}

it('creates a purchase order with whole catalog purchase units at or above its MOQ', function () {
    ['admin' => $admin, 'variant' => $variant, 'purchaseUnit' => $purchaseUnit, 'catalog' => $catalog] = purchaseOrderValidationContext();

    actingAs($admin)
        ->post(route('purchase-orders.store'), purchaseOrderItemPayload($catalog, $variant, $purchaseUnit->id, 2))
        ->assertRedirect();

    $purchaseOrder = PurchaseOrder::query()->sole();
    $lineItem = PurchaseOrderProduct::query()->where('purchase_order_id', $purchaseOrder->id)->sole();

    expect($lineItem->quantity)->toBe('2.0000');
    expect($lineItem->unit_id)->toBe($purchaseUnit->id);
});

it('rejects fractional purchase-unit quantities', function () {
    ['admin' => $admin, 'variant' => $variant, 'purchaseUnit' => $purchaseUnit, 'catalog' => $catalog] = purchaseOrderValidationContext();

    actingAs($admin)
        ->from(route('purchase-orders.create'))
        ->post(route('purchase-orders.store'), purchaseOrderItemPayload($catalog, $variant, $purchaseUnit->id, 2.5))
        ->assertRedirect(route('purchase-orders.create'))
        ->assertSessionHasErrors('items.0.quantity');
});

it('rejects quantities below the catalog MOQ', function () {
    ['admin' => $admin, 'variant' => $variant, 'purchaseUnit' => $purchaseUnit, 'catalog' => $catalog] = purchaseOrderValidationContext();

    actingAs($admin)
        ->from(route('purchase-orders.create'))
        ->post(route('purchase-orders.store'), purchaseOrderItemPayload($catalog, $variant, $purchaseUnit->id, 1))
        ->assertRedirect(route('purchase-orders.create'))
        ->assertSessionHasErrors('items.0.quantity');
});

it('rejects catalog entries paired with another product variant or unit', function () {
    ['admin' => $admin, 'variant' => $variant, 'catalog' => $catalog] = purchaseOrderValidationContext();
    $otherVariant = ProductVariant::factory()->for(Product::factory())->create();
    $otherUnit = ProductVariantUnit::query()->create([
        'product_variant_id' => $variant->id,
        'type' => 'purchase',
        'name' => 'Pack',
        'conversion_factor' => 10,
        'status' => 'active',
        'sort_order' => 2,
    ]);

    actingAs($admin)
        ->from(route('purchase-orders.create'))
        ->post(route('purchase-orders.store'), purchaseOrderItemPayload($catalog, $otherVariant, $otherUnit->id, 2))
        ->assertRedirect(route('purchase-orders.create'))
        ->assertSessionHasErrors(['items.0.product_variant_id', 'items.0.unit_id']);
});

it('applies whole-unit and MOQ validation when updating draft purchase orders', function () {
    ['admin' => $admin, 'vendor' => $vendor, 'variant' => $variant, 'purchaseUnit' => $purchaseUnit, 'catalog' => $catalog] = purchaseOrderValidationContext();
    $purchaseOrder = PurchaseOrder::factory()->create(['vendor_id' => $vendor->id]);

    actingAs($admin)
        ->from(route('purchase-orders.edit', $purchaseOrder))
        ->put(route('purchase-orders.update', $purchaseOrder), purchaseOrderItemPayload($catalog, $variant, $purchaseUnit->id, 1))
        ->assertRedirect(route('purchase-orders.edit', $purchaseOrder))
        ->assertSessionHasErrors('items.0.quantity');
});
