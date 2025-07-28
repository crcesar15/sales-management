<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasurementUnit>
 */
final class MeasurementUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word;

        return [
            'name' => $word,
            'abbreviation' => mb_strtoupper(mb_substr($word, 0, 2)),
        ];
    }
}
