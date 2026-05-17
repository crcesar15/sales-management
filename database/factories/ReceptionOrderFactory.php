<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\ReceptionOrder;
use App\Models\Store;
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
            'store_id' => Store::factory(),
            'reception_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'uncompleted', 'completed', 'cancelled']),
        ];
    }
}
