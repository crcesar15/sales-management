<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
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
            'measurement_unit_id' => random_int(1, 10),
            'name' => fake()->text(20),
            'description' => fake()->sentence(10),
            'status' => fake()->randomElement(['active', 'inactive', 'archived']),
        ];
    }
}
