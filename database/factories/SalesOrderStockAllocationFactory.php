<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderStockAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrderStockAllocation>
 */
final class SalesOrderStockAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_order_item_id' => SalesOrderItem::factory(),
            'batch_id' => Batch::factory(),
            'quantity' => fake()->numberBetween(1, 10),
        ];
    }
}
