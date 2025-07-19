<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SaleOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleOrder>
 */
final class SaleOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 100, 1000);

        return [
            'status' => 'draft',
            'payment_method' => 'cash',
            'sub_total' => $total,
            'total' => $total,
        ];
    }
}
