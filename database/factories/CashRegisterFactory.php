<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
final class CashRegisterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->randomElement(['Register 1', 'Register 2', 'Front Desk', 'Back Office']),
            'code' => fake()->unique()->lexify('REG???'),
            'status' => CashRegisterStatus::ACTIVE->value,
            'is_default' => false,
        ];
    }
}
