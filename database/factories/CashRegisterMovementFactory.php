<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashMovementType;
use App\Models\CashRegisterMovement;
use App\Models\CashRegisterShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegisterMovement>
 */
final class CashRegisterMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cash_register_shift_id' => CashRegisterShift::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(CashMovementType::cases())->value,
            'amount' => fake()->randomFloat(2, 1, 500),
            'reason' => fake()->sentence(),
        ];
    }
}
