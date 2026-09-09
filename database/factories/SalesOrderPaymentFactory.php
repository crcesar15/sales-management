<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\SalesOrder;
use App\Models\SalesOrderPayment;
use App\Models\User;
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
            'user_id' => User::factory(),
            'cash_register_shift_id' => null,
            'payment_method' => fake()->randomElement(PaymentMethod::cases())->value,
            'amount' => fake()->randomFloat(2, 10, 500),
            'tendered_amount' => null,
            'change_amount' => 0,
            'reference' => null,
        ];
    }
}
