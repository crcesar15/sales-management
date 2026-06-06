<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashRegisterShiftStatus;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegisterShift>
 */
final class CashRegisterShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cash_register_id' => CashRegister::factory(),
            'user_id' => User::factory(),
            'status' => CashRegisterShiftStatus::OPEN->value,
            'opening_balance' => fake()->randomFloat(2, 0, 500),
            'closing_balance' => null,
            'expected_closing' => null,
            'difference' => null,
            'opened_at' => now(),
            'closed_at' => null,
            'notes' => null,
        ];
    }
}
