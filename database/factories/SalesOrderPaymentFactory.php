<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\SalesOrder;
use App\Models\SalesOrderPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrderPayment>
 */
final class SalesOrderPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'payment_method' => fake()->randomElement(PaymentMethod::cases())->value,
            'amount' => fake()->randomFloat(2, 10, 500),
            'reference' => null,
        ];
    }
}
