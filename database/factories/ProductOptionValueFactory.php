<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOptionValue>
 */
final class ProductOptionValueFactory extends Factory
{
    private const OPTION_VALUES = [
        'Color' => ['Red', 'Blue', 'Green', 'Black', 'White', 'Yellow', 'Orange', 'Purple', 'Pink', 'Brown', 'Gray', 'Navy'],
        'Size' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],
        'Material' => ['Cotton', 'Polyester', 'Leather', 'Wood', 'Metal', 'Glass', 'Ceramic', 'Silk', 'Wool', 'Linen'],
        'Warranty' => ['6 Months', '1 Year', '2 Years', '3 Years', '5 Years', 'Lifetime'],
        'Capacity' => ['250ml', '500ml', '750ml', '1L', '2L', '5L', '10L'],
        'Style' => ['Classic', 'Modern', 'Vintage', 'Minimalist', 'Rustic', 'Industrial'],
        'Flavor' => ['Vanilla', 'Chocolate', 'Strawberry', 'Mint', 'Caramel', 'Lemon', 'Coffee'],
        'Texture' => ['Smooth', 'Matte', 'Glossy', 'Textured', 'Satin', 'Brushed'],
        'Pattern' => ['Solid', 'Striped', 'Plaid', 'Polka Dot', 'Geometric', 'Floral', 'Abstract'],
        'Finish' => ['Polished', 'Brushed', 'Matte', 'Glossy', 'Satin', 'Hammered'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => $this->faker->word(),
        ];
    }

    /**
     * Provide a contextual value for a given option name.
     */
    public function forOption(string $optionName): static
    {
        return $this->state(fn (array $attributes): array => [
            'value' => $this->faker->randomElement(
                self::OPTION_VALUES[$optionName] ?? [$this->faker->word()]
            ),
        ]);
    }

    /**
     * Create a value with a specific string (used by seeder for deterministic Cartesian products).
     */
    public function withValue(string $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'value' => $value,
        ]);
    }
}
