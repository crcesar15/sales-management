<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
final class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'order_date' => fake()->date(),
            'expected_arrival_date' => fake()->date(),
            'sub_total' => 0,
            'total' => 0,
            'status' => 'draft',
        ];
    }
}
