<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expiry_date' => $this->faker->date(),
            'initial_quantity' => $this->faker->numberBetween(1, 100),
            'remaining_quantity' => $this->faker->numberBetween(1, 100),
            'missing_quantity' => $this->faker->numberBetween(1, 100),
            'sold_quantity' => $this->faker->numberBetween(1, 100),
            'transferred_quantity' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['queued', 'active', 'closed']),
        ];
    }
}
