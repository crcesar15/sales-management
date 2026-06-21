<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * @param  array<string, mixed>  $overrides
 */
function createVariant(array $overrides = []): ProductVariant
{
    $product = Product::factory()->create();

    return ProductVariant::factory()->create([
        'product_id' => $product->id,
        ...$overrides,
    ]);
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

it('admin can view inventory', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('inventory.variants'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/Index')
            ->has('variants')
            ->has('stores')
            ->has('categories')
            ->has('brands')
        );
});

it('salesman is denied inventory', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('inventory.variants'))
        ->assertForbidden();
});

it('guest is redirected from inventory', function () {
    get(route('inventory.variants'))
        ->assertRedirect(route('login'));
});

it('admin can view inventory variant detail', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $variant = createVariant();

    actingAs($admin)
        ->get(route('inventory.variants.show', ['product' => $variant->product_id, 'variant' => $variant->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Inventory/Show/Index')
            ->has('product')
            ->has('variant')
            ->has('stores')
        );
});

/*
|--------------------------------------------------------------------------
| Stock Aggregation
|--------------------------------------------------------------------------
*/

it('aggregates stock from active batches only', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $variant = createVariant();

    Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'remaining_quantity' => 20,
        'status' => 'active',
    ]);
    Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'remaining_quantity' => 50,
        'status' => 'closed',
    ]);

    $variant->recalculateStock();

    actingAs($admin)
        ->get(route('inventory.variants'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.data.0.total_stock', 20)
        );
});

it('shows zero stock when no batches exist', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    createVariant();

    actingAs($admin)
        ->get(route('inventory.variants'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.data.0.total_stock', 0)
            ->where('variants.data.0.is_low_stock', true)
        );
});

it('shows per-store breakdown for a variant', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store1 = Store::factory()->create(['name' => 'Main Branch']);
    $store2 = Store::factory()->create(['name' => 'North Branch']);
    $variant = createVariant();

    Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store1->id,
        'remaining_quantity' => 30,
        'status' => 'active',
    ]);
    Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store2->id,
        'remaining_quantity' => 15,
        'status' => 'active',
    ]);

    actingAs($admin)
        ->get(route('inventory.variants.show', ['product' => $variant->product_id, 'variant' => $variant->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variant.stock', 45)
            ->where('stores', fn ($stores) => count($stores) === 2)
        );
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

it('filters by variant status', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    createVariant(['status' => 'active']);
    createVariant(['status' => 'inactive']);

    actingAs($admin)
        ->get(route('inventory.variants', ['status' => 'active']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.meta.total', 1)
            ->where('variants.data.0.status', 'active')
        );
});

it('filters by store', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store1 = Store::factory()->create(['status' => 'active']);
    $store2 = Store::factory()->create(['status' => 'active']);
    $variant = createVariant();

    Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store1->id,
        'remaining_quantity' => 20,
        'status' => 'active',
    ]);
    Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => $store2->id,
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);

    actingAs($admin)
        ->get(route('inventory.variants', ['store_id' => $store1->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.data.0.total_stock', 20)
        );
});

it('filters by category', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category);
    createVariant(['product_id' => $product->id]);
    createVariant();

    actingAs($admin)
        ->get(route('inventory.variants', ['category_id' => $category->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.meta.total', 1)
        );
});

it('filters by brand', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);
    createVariant(['product_id' => $product->id]);
    createVariant();

    actingAs($admin)
        ->get(route('inventory.variants', ['brand_id' => $brand->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.meta.total', 1)
        );
});

it('filters by low stock only', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $variantWithStock = createVariant();
    $variantNoStock = createVariant();

    Batch::factory()->create([
        'product_variant_id' => $variantWithStock->id,
        'store_id' => $store->id,
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);
    $variantWithStock->recalculateStock();

    actingAs($admin)
        ->get(route('inventory.variants', ['low_stock' => true]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.meta.total', 1)
            ->where('variants.data.0.id', $variantNoStock->id)
        );
});

it('searches by product name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $product = Product::factory()->create(['name' => 'Running Shoe']);
    createVariant(['product_id' => $product->id]);

    $otherProduct = Product::factory()->create(['name' => 'Winter Boot']);
    createVariant(['product_id' => $otherProduct->id]);

    actingAs($admin)
        ->get(route('inventory.variants', ['search' => 'Running']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.meta.total', 1)
            ->where('variants.data.0.product_name', 'Running Shoe')
        );
});

it('applies status and low_stock filters together', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $activeWithStock = createVariant(['status' => 'active']);
    Batch::factory()->create([
        'product_variant_id' => $activeWithStock->id,
        'store_id' => $store->id,
        'remaining_quantity' => 10,
        'status' => 'active',
    ]);
    $activeWithStock->recalculateStock();

    $activeNoStock = createVariant(['status' => 'active']);
    createVariant(['status' => 'inactive']);

    actingAs($admin)
        ->get(route('inventory.variants', ['status' => 'active', 'low_stock' => true]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.meta.total', 1)
            ->where('variants.data.0.id', $activeNoStock->id)
        );
});

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

it('paginates results', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    for ($i = 0; $i < 20; $i++) {
        createVariant();
    }

    actingAs($admin)
        ->get(route('inventory.variants', ['per_page' => 5]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('variants.meta.per_page', 5)
            ->where('variants.meta.total', 20)
            ->where('variants.data', fn ($data) => count($data) === 5)
        );
});
