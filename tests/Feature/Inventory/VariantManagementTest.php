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
