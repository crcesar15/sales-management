<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
final class SalesOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subTotal = fake()->randomFloat(2, 100, 1000);

        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'cash_register_shift_id' => null,
            'status' => SalesOrderStatus::DRAFT->value,
            'payment_status' => SalesOrderPaymentStatus::PENDING->value,
            'discount_type' => DiscountType::FLAT->value,
            'discount_value' => 0,
            'sub_total' => $subTotal,
            'discount' => 0,
            'tax_amount' => 0,
            'total' => $subTotal,
            'token' => null,
            'notes' => null,
        ];
    }
}
