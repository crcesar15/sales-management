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
            'identifier' => fake()->ean13(),
            'name' => fake()->text(20),
            'description' => fake()->paragraph(2),
            'price' => fake()->randomFloat(2, 0, 3000),
        ];
    }
}
