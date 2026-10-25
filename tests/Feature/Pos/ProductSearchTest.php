<?php

declare(strict_types=1);

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\Store;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

use function Pest\Laravel\actingAs;

function createProductMedia(Product $product, string $fileName, int $order = 1, array $generatedConversions = ['thumb' => true]): Media
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
        'generated_conversions' => $generatedConversions,
        'responsive_images' => [],
        'order_column' => $order,
    ]);
}

beforeEach(function () {
    $this->actor = User::factory()->create();

    $this->product = Product::factory()->create(['name' => 'Searchable product']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'identifier' => 'SKU-0001',
        'barcode' => '7501234567890',
    ]);
});

it('finds a variant by its exact barcode', function () {
    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => '7501234567890']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $this->variant->id)
        ->assertJsonPath('data.0.barcode', '7501234567890')
        ->assertJsonPath('data.0.identifier', 'SKU-0001');
});

it('finds a variant by a partial barcode match', function () {
    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => '234567']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $this->variant->id);
});

it('ranks an exact barcode match above partial matches', function () {
    $product = Product::factory()->create(['name' => 'Other product']);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'identifier' => 'SKU-OTHER',
        'barcode' => '07501234567890',
    ]);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => '7501234567890']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $this->variant->id)
        ->assertJsonPath('data.1.id', $product->variants()->first()->id);
});

it('still finds variants by product name and identifier', function () {
    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $this->variant->id);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'SKU-0001']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', $this->variant->id);
});

it('returns no results when the barcode does not match any variant', function () {
    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => '9999999999999']))
        ->assertSuccessful()
        ->assertJsonPath('data', []);
});

it('includes the assigned variant image as thumbnail when requested', function () {
    $media = createProductMedia($this->product, 'variant.jpg');
    $this->variant->images()->attach($media->id, ['position' => 1]);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product', 'includes' => 'thumbnail']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.thumbnail_url', $media->getUrl('thumb'));
});

it('prefers the assigned variant image over the product image', function () {
    createProductMedia($this->product, 'product.jpg');
    $variantMedia = createProductMedia($this->product, 'variant.jpg');
    $this->variant->images()->attach($variantMedia->id, ['position' => 1]);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product', 'includes' => 'thumbnail']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.thumbnail_url', $variantMedia->getUrl('thumb'));
});

it('uses the first assigned variant image by pivot position', function () {
    $second = createProductMedia($this->product, 'second.jpg', order: 2);
    $first = createProductMedia($this->product, 'first.jpg', order: 1);
    $this->variant->images()->attach([
        $second->id => ['position' => 2],
        $first->id => ['position' => 1],
    ]);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product', 'includes' => 'thumbnail']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.thumbnail_url', $first->getUrl('thumb'));
});

it('falls back to the first product image when the variant has none assigned', function () {
    $productMedia = createProductMedia($this->product, 'product-1.jpg', order: 1);
    createProductMedia($this->product, 'product-2.jpg', order: 2);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product', 'includes' => 'thumbnail']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.thumbnail_url', $productMedia->getUrl('thumb'));
});

it('returns a null thumbnail when neither the variant nor the product has images', function () {
    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product', 'includes' => 'thumbnail']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.thumbnail_url', null);
});

it('uses the original image URL when the thumb conversion is not generated', function () {
    $media = createProductMedia($this->product, 'no-conversion.jpg', generatedConversions: []);
    $this->variant->images()->attach($media->id, ['position' => 1]);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product', 'includes' => 'thumbnail']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.thumbnail_url', $media->getUrl());
});

it('omits the thumbnail unless the include is requested', function () {
    $media = createProductMedia($this->product, 'variant.jpg');
    $this->variant->images()->attach($media->id, ['position' => 1]);

    $response = actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', ['filter' => 'Searchable product']))
        ->assertSuccessful();

    expect($response->json('data.0'))->not->toHaveKey('thumbnail_url');
});

it('includes thumbnails alongside sale units and store-scoped stock', function () {
    $store = Store::factory()->create();

    Batch::factory()->create([
        'product_variant_id' => $this->variant->id,
        'store_id' => $store->id,
        'remaining_quantity' => 5,
        'initial_quantity' => 5,
        'status' => 'active',
    ]);

    ProductVariantUnit::query()->create([
        'product_variant_id' => $this->variant->id,
        'type' => 'sale',
        'name' => 'Box',
        'conversion_factor' => 12,
        'price' => 250,
        'status' => 'active',
        'sort_order' => 1,
    ]);

    $media = createProductMedia($this->product, 'variant.jpg');
    $this->variant->images()->attach($media->id, ['position' => 1]);

    actingAs($this->actor, 'sanctum')
        ->getJson(route('api.v1.variants.search', [
            'filter' => 'Searchable product',
            'includes' => 'saleUnits,thumbnail',
            'store_id' => $store->id,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('data.0.stock', 5)
        ->assertJsonPath('data.0.sale_units.0.name', 'Box')
        ->assertJsonPath('data.0.sale_units.0.conversion_factor', 12)
        ->assertJsonPath('data.0.thumbnail_url', $media->getUrl('thumb'));
});
