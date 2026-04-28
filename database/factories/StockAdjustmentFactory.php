<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdjustmentReason;
use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
final class StockAdjustmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'batch_id' => Batch::factory(),
            'quantity_change' => fake()->numberBetween(-50, 50),
            'reason' => fake()->randomElement(AdjustmentReason::cases())->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
