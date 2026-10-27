<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\Store;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
});

function createGalleryMedia(Product $product, string $fileName, int $order = 1): Media
{
    return Media::create([
        'model_type' => Product::class,
        'model_id' => $product->id,
        'collection_name' => 'images',
        'name' => $fileName,
        'file_name' => $fileName,
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 1024,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => ['thumb' => true],
        'responsive_images' => [],
        'order_column' => $order,
    ]);
}

it('renders the gallery without an open shift', function (): void {
    $product = Product::factory()->create(['name' => 'Display product']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    actingAs($this->user)
        ->get(route('pos.product-gallery.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pos/ProductGallery/Index')
            ->where('variants.data.0.id', $variant->id)
            ->where('variants.data.0.product_name', 'Display product')
            ->where('variants.data.0.is_available', false));
});

it('accepts textual boolean availability from the query string', function (): void {
    ProductVariant::factory()->create(['product_id' => Product::factory()->create()->id]);

    actingAs($this->user)
        ->get('/pos/product-gallery?filter=aro&brand_id=&category_id=&available=false&page=1')
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.available', false));
});

it('forbids gallery access without POS permission', function (): void {
    actingAs(User::factory()->create())
        ->getJson(route('pos.product-gallery.index'))
        ->assertForbidden();
});

it('redirects guests to login', function (): void {
    get(route('pos.product-gallery.index'))->assertRedirect(route('login'));
});

it('only shows active variants for active products', function (): void {
    $activeProduct = Product::factory()->create(['name' => 'Active product']);
    $activeVariant = ProductVariant::factory()->create(['product_id' => $activeProduct->id, 'status' => 'active']);
    $inactiveProduct = Product::factory()->inactive()->create();
    $inactiveProductVariant = ProductVariant::factory()->create(['product_id' => $inactiveProduct->id]);
    $inactiveVariant = ProductVariant::factory()->inactive()->create(['product_id' => $activeProduct->id]);

    actingAs($this->user)
        ->get(route('pos.product-gallery.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('variants.data.0.id', $activeVariant->id)
            ->missing('variants.data.1')
            ->where('variants.meta.total', 1));

    expect([$inactiveProductVariant->id, $inactiveVariant->id])->not->toContain($activeVariant->id);
});

it('searches by variant option, identifier, and barcode', function (): void {
    $product = Product::factory()->create(['name' => 'Travel mug']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'identifier' => 'MUG-BLUE',
        'barcode' => '1234567890',
    ]);
    $option = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Color']);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Blue']);
    $variant->values()->attach($value);

    foreach (['Blue', 'MUG-BLUE', '1234567890'] as $filter) {
        actingAs($this->user)
            ->get(route('pos.product-gallery.index', ['filter' => $filter]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('variants.data.0.id', $variant->id));
    }
});

it('filters the catalog by brand and category', function (): void {
    $brand = Brand::factory()->create(['name' => 'Northwind']);
    $category = Category::factory()->create(['name' => 'Travel']);
    $matchingProduct = Product::factory()->create(['brand_id' => $brand->id]);
    $matchingProduct->categories()->attach($category);
    $matchingVariant = ProductVariant::factory()->create(['product_id' => $matchingProduct->id]);
    ProductVariant::factory()->create(['product_id' => Product::factory()->create()->id]);

    actingAs($this->user)
        ->get(route('pos.product-gallery.index', ['brand_id' => $brand->id, 'category_id' => $category->id]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('variants.data.0.id', $matchingVariant->id)
            ->where('variants.meta.total', 1)
            ->where('filters.brand_id', $brand->id)
            ->where('filters.category_id', $category->id));
});

it('uses sellable inventory across all stores for availability', function (): void {
    $variant = ProductVariant::factory()->create(['product_id' => Product::factory()->create()->id]);
    Batch::factory()->create([
        'product_variant_id' => $variant->id,
        'store_id' => Store::factory()->create()->id,
        'status' => 'active',
        'remaining_quantity' => 3,
        'expiry_date' => now()->addDay(),
    ]);
    $unavailableVariant = ProductVariant::factory()->create(['product_id' => Product::factory()->create()->id]);
    Batch::factory()->create([
        'product_variant_id' => $unavailableVariant->id,
        'store_id' => Store::factory()->create()->id,
        'status' => 'active',
        'remaining_quantity' => 3,
        'expiry_date' => now()->subDay(),
    ]);

    actingAs($this->user)
        ->get(route('pos.product-gallery.index', ['available' => true]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('variants.data.0.id', $variant->id)
            ->where('variants.data.0.is_available', true)
            ->where('variants.meta.total', 1));
});

it('returns a customer-safe detailed variant preview', function (): void {
    $product = Product::factory()->create(['name' => 'Ceramic mug', 'description' => 'A durable travel mug.']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 12.5]);
    ProductVariantUnit::query()->create([
        'product_variant_id' => $variant->id,
        'type' => 'sale',
        'name' => 'Pack of 2',
        'conversion_factor' => 2,
        'price' => 20,
        'status' => 'active',
        'sort_order' => 1,
    ]);

    actingAs($this->user, 'sanctum')
        ->getJson(route('api.v1.pos.product-gallery.show', $variant))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $variant->id)
        ->assertJsonPath('data.product_name', 'Ceramic mug')
        ->assertJsonPath('data.description', 'A durable travel mug.')
        ->assertJsonPath('data.price', 12.5)
        ->assertJsonPath('data.sale_units.0.name', 'Pack of 2')
        ->assertJsonMissingPath('data.purchase_price')
        ->assertJsonMissingPath('data.stock');
});

it('uses assigned variant images before product images in the preview', function (): void {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    createGalleryMedia($product, 'product.jpg');
    $variantImage = createGalleryMedia($product, 'variant.jpg');
    $variant->images()->attach($variantImage->id, ['position' => 1]);

    actingAs($this->user, 'sanctum')
        ->getJson(route('api.v1.pos.product-gallery.show', $variant))
        ->assertSuccessful()
        ->assertJsonPath('data.images.0.id', $variantImage->id)
        ->assertJsonCount(1, 'data.images');
});

it('falls back to product images when a variant has none assigned', function (): void {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $image = createGalleryMedia($product, 'product.jpg');

    actingAs($this->user, 'sanctum')
        ->getJson(route('api.v1.pos.product-gallery.show', $variant))
        ->assertSuccessful()
        ->assertJsonPath('data.images.0.id', $image->id);
});

it('does not expose inactive variants through the detail endpoint', function (): void {
    $variant = ProductVariant::factory()->inactive()->create(['product_id' => Product::factory()->create()->id]);

    actingAs($this->user, 'sanctum')
        ->getJson(route('api.v1.pos.product-gallery.show', $variant))
        ->assertNotFound();
});
