<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\User;

use function Pest\Laravel\actingAs;

function inventoryAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    return $user;
}

/** @return array<string, bool|float|int|string|null> */
function variantUpdatePayload(ProductVariant $variant): array
{
    return [
        'identifier' => $variant->identifier,
        'barcode' => $variant->barcode,
        'purchase_price' => null,
        'margin_type' => 'percent',
        'margin_value' => null,
        'price' => 25.50,
        'minimum_stock_level' => 5,
        'has_expiration' => true,
        'status' => 'active',
    ];
}

it('provides sibling variants to the canonical workspace', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($category);
    $first = ProductVariant::factory()->create(['product_id' => $product->id]);
    $second = ProductVariant::factory()->create(['product_id' => $product->id]);

    actingAs(inventoryAdmin())
        ->get(route('inventory.variants.show', $first))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/Show/Index')
            ->has('siblings', 2)
            ->where('siblings.0.id', $first->id)
            ->where('siblings.1.id', $second->id)
            ->where('product.brand.name', $brand->name)
            ->has('product.categories', 1)
            ->where('product.categories.0.name', $category->name)
        );
});

it('updates variant overview fields with nullable purchase pricing', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create();

    actingAs(inventoryAdmin())
        ->put(route('inventory.variant.update', $variant), variantUpdatePayload($variant))
        ->assertRedirect();

    $variant->refresh();

    expect($variant->purchase_price)->toBeNull()
        ->and($variant->margin_value)->toBeNull()
        ->and((float) $variant->price)->toBe(25.5)
        ->and($variant->minimum_stock_level)->toBe(5)
        ->and($variant->has_expiration)->toBeTrue();
});

it('replicates exact pricing to sibling variants only', function () {
    $product = Product::factory()->create();
    $source = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => 15,
        'purchase_price' => 8,
        'margin_type' => 'amount',
        'margin_value' => 7,
        'minimum_stock_level' => 2,
        'has_expiration' => false,
    ]);
    $sibling = ProductVariant::factory()->inactive()->create([
        'product_id' => $product->id,
        'price' => 45,
        'purchase_price' => 20,
        'margin_type' => 'percent',
        'margin_value' => 50,
        'minimum_stock_level' => 12,
        'has_expiration' => true,
    ]);
    $otherProductVariant = ProductVariant::factory()->for(Product::factory())->create([
        'price' => 75,
        'purchase_price' => 40,
        'margin_type' => 'percent',
        'margin_value' => 25,
    ]);

    actingAs(inventoryAdmin())
        ->put(route('inventory.variant.update', $source), [
            ...variantUpdatePayload($source),
            'purchase_price' => 10,
            'margin_type' => 'percent',
            'margin_value' => 20,
            'price' => 12.5,
            'replicate_pricing' => true,
            'replicate_inventory_policy' => false,
        ])
        ->assertRedirect();

    $sibling->refresh();
    $otherProductVariant->refresh();

    expect((float) $sibling->purchase_price)->toBe(10.0)
        ->and($sibling->margin_type->value)->toBe('percent')
        ->and((float) $sibling->margin_value)->toBe(20.0)
        ->and((float) $sibling->price)->toBe(12.5)
        ->and($sibling->minimum_stock_level)->toBe(12)
        ->and($sibling->has_expiration)->toBeTrue()
        ->and($sibling->status)->toBe('inactive')
        ->and((float) $otherProductVariant->purchase_price)->toBe(40.0)
        ->and((float) $otherProductVariant->price)->toBe(75.0);
});

it('replicates inventory policy without changing sibling statuses', function () {
    $product = Product::factory()->create();
    $source = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'minimum_stock_level' => 3,
        'has_expiration' => false,
        'status' => 'inactive',
    ]);
    $sibling = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => 45,
        'minimum_stock_level' => 20,
        'has_expiration' => false,
        'status' => 'archived',
    ]);

    actingAs(inventoryAdmin())
        ->put(route('inventory.variant.update', $source), [
            ...variantUpdatePayload($source),
            'minimum_stock_level' => null,
            'has_expiration' => true,
            'status' => 'active',
            'replicate_pricing' => false,
            'replicate_inventory_policy' => true,
        ])
        ->assertRedirect();

    $source->refresh();
    $sibling->refresh();

    expect($source->status)->toBe('active')
        ->and($sibling->minimum_stock_level)->toBeNull()
        ->and($sibling->has_expiration)->toBeTrue()
        ->and((float) $sibling->price)->toBe(45.0)
        ->and($sibling->status)->toBe('archived');
});

it('rejects a duplicate variant identifier', function () {
    $product = Product::factory()->create();
    $existing = ProductVariant::factory()->create(['product_id' => $product->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    actingAs(inventoryAdmin())
        ->put(route('inventory.variant.update', $variant), [
            ...variantUpdatePayload($variant),
            'identifier' => $existing->identifier,
        ])
        ->assertSessionHasErrors('identifier');
});

it('denies overview updates without inventory edit permission', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create();
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->putJson(route('inventory.variant.update', $variant), variantUpdatePayload($variant))
        ->assertForbidden();
});

it('redirects a manually created variant to its configuration workspace', function () {
    $product = Product::factory()->create();
    $option = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size']);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'M']);

    $response = actingAs(inventoryAdmin())
        ->post(route('variant.store', $product), ['option_value_ids' => [$value->id]]);

    $response->assertSessionHasNoErrors();
    $variant = ProductVariant::query()->latest('id')->firstOrFail();

    $response->assertRedirect(route('inventory.variants.show', $variant));
});

it('rejects a nested variant route for a different product', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create();
    $otherProduct = Product::factory()->create();

    actingAs(inventoryAdmin())
        ->delete(route('variant.destroy', ['product' => $otherProduct, 'variant' => $variant]))
        ->assertNotFound();
});

it('requires one option value for every product option when creating a variant', function () {
    $product = Product::factory()->create();
    $size = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size']);
    $color = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Color']);
    $sizeValue = ProductOptionValue::factory()->create(['product_option_id' => $size->id, 'value' => 'M']);
    ProductOptionValue::factory()->create(['product_option_id' => $color->id, 'value' => 'Blue']);

    actingAs(inventoryAdmin())
        ->post(route('variant.store', $product), ['option_value_ids' => [$sizeValue->id]])
        ->assertSessionHasErrors('option_value_ids');
});

it('creates sale and purchase units from the canonical workspace', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create();
    $url = ['product' => $variant->product_id, 'variant' => $variant->id];

    actingAs(inventoryAdmin())
        ->post(route('variant.units.store', $url), [
            'type' => 'sale',
            'name' => 'Box',
            'conversion_factor' => 12,
            'price' => 120,
            'status' => 'active',
            'sort_order' => 1,
        ])
        ->assertRedirect();

    actingAs(inventoryAdmin())
        ->post(route('variant.units.store', $url), [
            'type' => 'purchase',
            'name' => 'Carton',
            'conversion_factor' => 24,
            'price' => 240,
            'status' => 'active',
            'sort_order' => 2,
        ])
        ->assertRedirect();

    expect(ProductVariantUnit::query()->where([
        'product_variant_id' => $variant->id,
        'type' => 'sale',
        'name' => 'Box',
        'price' => 120,
    ])->exists())->toBeTrue();
    expect(ProductVariantUnit::query()->where([
        'product_variant_id' => $variant->id,
        'type' => 'purchase',
        'name' => 'Carton',
        'price' => null,
    ])->exists())->toBeTrue();
});

it('replicates a sale unit to all sibling variants and updates matching units', function () {
    $product = Product::factory()->create();
    $source = ProductVariant::factory()->for($product)->create();
    $matchingSibling = ProductVariant::factory()->for($product)->create(['status' => 'inactive']);
    $missingSibling = ProductVariant::factory()->for($product)->create(['status' => 'archived']);
    $matchingUnit = ProductVariantUnit::query()->create([
        'product_variant_id' => $matchingSibling->id,
        'type' => 'sale',
        'name' => 'Box',
        'conversion_factor' => 6,
        'price' => 60,
        'status' => 'inactive',
        'sort_order' => 4,
    ]);
    $matchingUnitId = $matchingUnit->id;
    ProductVariantUnit::query()->create([
        'product_variant_id' => $matchingSibling->id,
        'type' => 'sale',
        'name' => 'Pack',
        'conversion_factor' => 3,
        'price' => 30,
        'status' => 'active',
        'sort_order' => 1,
    ]);

    actingAs(inventoryAdmin())
        ->post(route('variant.units.store', ['product' => $product, 'variant' => $source]), [
            'type' => 'sale',
            'name' => 'Box',
            'conversion_factor' => 12,
            'price' => 120,
            'status' => 'active',
            'sort_order' => 2,
            'replicate_to_siblings' => true,
        ])
        ->assertRedirect();

    $matchingUnit->refresh();
    expect($matchingUnit->id)->toBe($matchingUnitId);
    expect($matchingUnit->conversion_factor)->toBe(12);
    expect($matchingUnit->price)->toBe('120.00');
    expect($matchingUnit->status)->toBe('active');
    expect($matchingUnit->sort_order)->toBe(2);
    expect(ProductVariantUnit::query()->where([
        'product_variant_id' => $missingSibling->id,
        'type' => 'sale',
        'name' => 'Box',
        'conversion_factor' => 12,
        'price' => 120,
        'status' => 'active',
        'sort_order' => 2,
    ])->exists())->toBeTrue();
    expect(ProductVariantUnit::query()->where([
        'product_variant_id' => $matchingSibling->id,
        'type' => 'sale',
        'name' => 'Pack',
    ])->exists())->toBeTrue();
});

it('replicates an edited purchase unit using its original name', function () {
    $product = Product::factory()->create();
    $source = ProductVariant::factory()->for($product)->create();
    $sibling = ProductVariant::factory()->for($product)->create();
    $sourceUnit = ProductVariantUnit::query()->create([
        'product_variant_id' => $source->id,
        'type' => 'purchase',
        'name' => 'Carton',
        'conversion_factor' => 24,
        'price' => null,
        'status' => 'active',
        'sort_order' => 1,
    ]);
    $siblingUnit = ProductVariantUnit::query()->create([
        'product_variant_id' => $sibling->id,
        'type' => 'purchase',
        'name' => 'Carton',
        'conversion_factor' => 12,
        'price' => null,
        'status' => 'inactive',
        'sort_order' => 3,
    ]);
    $siblingUnitId = $siblingUnit->id;

    actingAs(inventoryAdmin())
        ->put(route('variant.units.update', ['product' => $product, 'variant' => $source, 'unit' => $sourceUnit]), [
            'name' => 'Case',
            'conversion_factor' => 48,
            'status' => 'active',
            'sort_order' => 2,
            'replicate_to_siblings' => true,
        ])
        ->assertRedirect();

    $siblingUnit->refresh();
    expect($siblingUnit->id)->toBe($siblingUnitId);
    expect($siblingUnit->name)->toBe('Case');
    expect($siblingUnit->conversion_factor)->toBe(48);
    expect($siblingUnit->price)->toBeNull();
    expect($siblingUnit->sort_order)->toBe(2);
});

it('rolls back replicated unit updates when a sibling has the new name', function () {
    $product = Product::factory()->create();
    $source = ProductVariant::factory()->for($product)->create();
    $sibling = ProductVariant::factory()->for($product)->create();
    $sourceUnit = ProductVariantUnit::query()->create([
        'product_variant_id' => $source->id,
        'type' => 'sale',
        'name' => 'Box',
        'conversion_factor' => 12,
        'price' => 120,
        'status' => 'active',
        'sort_order' => 1,
    ]);
    ProductVariantUnit::query()->create([
        'product_variant_id' => $sibling->id,
        'type' => 'sale',
        'name' => 'Box',
        'conversion_factor' => 12,
        'price' => 120,
        'status' => 'active',
        'sort_order' => 1,
    ]);
    ProductVariantUnit::query()->create([
        'product_variant_id' => $sibling->id,
        'type' => 'sale',
        'name' => 'Case',
        'conversion_factor' => 24,
        'price' => 240,
        'status' => 'active',
        'sort_order' => 2,
    ]);

    actingAs(inventoryAdmin())
        ->put(route('variant.units.update', ['product' => $product, 'variant' => $source, 'unit' => $sourceUnit]), [
            'name' => 'Case',
            'conversion_factor' => 48,
            'price' => 480,
            'status' => 'inactive',
            'sort_order' => 3,
            'replicate_to_siblings' => true,
        ])
        ->assertSessionHasErrors('replicate_to_siblings');

    $sourceUnit->refresh();
    expect($sourceUnit->name)->toBe('Box');
    expect($sourceUnit->conversion_factor)->toBe(12);
    expect($sourceUnit->price)->toBe('120.00');
    expect($sourceUnit->status)->toBe('active');
    expect($sourceUnit->sort_order)->toBe(1);
});

it('requires inventory edit permission to manage units and variant images', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create();
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);
    $url = ['product' => $variant->product_id, 'variant' => $variant->id];

    actingAs($salesman)
        ->postJson(route('variant.units.store', $url), [
            'type' => 'sale',
            'name' => 'Box',
            'conversion_factor' => 12,
            'price' => 120,
            'status' => 'active',
        ])
        ->assertForbidden();

    actingAs($salesman)
        ->putJson(route('variant.images.sync', $url), ['media_ids' => []])
        ->assertForbidden();
});

it('accepts an empty image selection and requires the image list to be submitted', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create();
    $url = ['product' => $variant->product_id, 'variant' => $variant->id];

    actingAs(inventoryAdmin())
        ->put(route('variant.images.sync', $url), ['media_ids' => []])
        ->assertRedirect();

    actingAs(inventoryAdmin())
        ->put(route('variant.images.sync', $url), [])
        ->assertSessionHasErrors('media_ids');
});
