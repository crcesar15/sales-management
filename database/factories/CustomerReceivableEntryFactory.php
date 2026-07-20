<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerReceivableEntry;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerReceivableEntry>
 */
final class CustomerReceivableEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'sales_order_id' => SalesOrder::factory(),
            'sales_order_payment_id' => null,
            'user_id' => User::factory(),
            'type' => 'charge',
            'amount' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
