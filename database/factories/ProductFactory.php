<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Products>
 */
final class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => random_int(1, 10),
            'measure_unit_id' => random_int(1, 10),
            'name' => fake()->text(20),
            'description' => fake()->sentence(10),
            'status' => fake()->randomElement(['active', 'inactive', 'archived']),
        ];
    }
}
