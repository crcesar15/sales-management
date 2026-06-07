<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrderItem>
 */
final class SalesOrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 1, 500);
        $lineTotal = round($quantity * $unitPrice, 2);

        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'sale_unit_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'conversion_factor' => 1,
            'line_total' => $lineTotal,
        ];
    }
}
