<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\ReceptionOrder;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
final class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'reception_order_id' => ReceptionOrder::factory(),
            'store_id' => Store::factory(),
            'expiry_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'initial_quantity' => fake()->numberBetween(1, 100),
            'remaining_quantity' => fake()->numberBetween(1, 100),
            'missing_quantity' => fake()->numberBetween(0, 10),
            'sold_quantity' => fake()->numberBetween(0, 50),
            'transferred_quantity' => fake()->numberBetween(0, 5),
            'status' => fake()->randomElement(['queued', 'active', 'closed']),
        ];
    }
}
