<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Products>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => 1,
            'brand_id' => rand(1, 10),
            'measure_unit_id' => rand(1, 10),
            'name' => fake()->text(20),
            'status' => $this->faker->randomElement(['active', 'inactive', 'archived']),
        ];
    }
}
