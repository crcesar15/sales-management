<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Products>
 */
class ProductsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => fake()->ean13(),
            'name' => fake()->text(20),
            'description' => fake()->paragraph(2),
            'price' => fake()->randomFloat(2, 0, 3000),
            'stock' => fake()->randomNumber(2),
            'brand' => fake()->randomElement(['Oster', 'Toshiba', 'TLC']),
        ];
    }
}
