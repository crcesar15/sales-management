<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
final class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expiry_date' => fake()->date(),
            'initial_quantity' => fake()->numberBetween(1, 100),
            'remaining_quantity' => fake()->numberBetween(1, 100),
            'missing_quantity' => fake()->numberBetween(1, 100),
            'sold_quantity' => fake()->numberBetween(1, 100),
            'transferred_quantity' => fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(['queued', 'active', 'closed']),
        ];
    }
}
