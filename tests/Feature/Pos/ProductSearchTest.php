<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

use function Pest\Laravel\actingAs;

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
        ->assertJsonPath('data.0.id', $this->variant->id);
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
