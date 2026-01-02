<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductOption>
 */
final class ProductOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $options = [
            'Color',
            'Size',
            'Material',
            'Warranty',
            'Capacity',
            'Style',
            'Flavor',
            'Texture',
            'Pattern',
            'Finish',
        ];

        return [
            // random from array
            'name' => $this->faker->randomElement($options),
        ];
    }
}
