<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\VariantStoreLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VariantStoreLocation> */
final class VariantStoreLocationFactory extends Factory
{
    public function definition(): array
    {
        return ['product_variant_id' => ProductVariant::factory()->for(Product::factory()), 'store_id' => Store::factory(), 'location_code' => 'A-01'];
    }
}
