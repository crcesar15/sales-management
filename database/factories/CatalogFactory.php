<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Catalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Catalog>
 */
final class CatalogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price' => fake()->randomFloat(2, 1, 1000),
            'payment_terms' => fake()->randomElement(['debit', 'credit']),
            'details' => fake()->text(100),
        ];
    }
}
