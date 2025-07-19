<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReceptionOrder;
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
            'reception_date' => fake()->date(),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
        ];
    }
}
