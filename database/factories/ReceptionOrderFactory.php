<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\ReceptionOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceptionOrder>
 */
final class ReceptionOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'reception_date' => fake()->date(),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
        ];
    }
}
