<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MeasureUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasureUnit>
 */
final class MeasureUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
            'description' => fake()->sentence,
        ];
    }
}
