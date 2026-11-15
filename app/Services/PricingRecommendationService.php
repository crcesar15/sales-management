<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class PricingRecommendationService
{
    /** @param list<array{product_variant_id: int, unit_id?: int|null, price: float|int|string, margin_type?: string, margin_value?: float|int|string|null, selling_price?: float|int|string, sale_factor?: int}> $items
     * @return list<array<string, mixed>>
     */
    public function preview(array $items): array
    {
        $variants = ProductVariant::query()->with(['activeSaleUnits', 'product'])->whereKey(array_column($items, 'product_variant_id'))->get()->keyBy('id');
        $units = ProductVariantUnit::query()->whereKey(array_filter(array_column($items, 'unit_id')))->get()->keyBy('id');
        $results = [];
        foreach ($items as $index => $item) {
            $variant = $variants->get($item['product_variant_id']) ?? throw new InvalidArgumentException('A selected product is no longer available.');
            $unit = isset($item['unit_id']) ? $units->get($item['unit_id']) : null;
            if (isset($item['unit_id']) && ($unit === null || $unit->product_variant_id !== $variant->id || $unit->type !== 'purchase' || $unit->status !== 'active')) {
                throw new InvalidArgumentException('Select a valid purchase unit for this product.');
            }
            $cost = $this->normalize((string) $item['price'], $unit->conversion_factor ?? 1);
            $type = $item['margin_type'] ?? $variant->margin_type->value;
            $target = array_key_exists('margin_value', $item) ? $item['margin_value'] : $variant->margin_value;
            $results[] = [
                'index' => $index, 'product_variant_id' => $variant->id, 'product_name' => $variant->product?->name,
                'previous_cost' => $variant->replacement_cost === null ? null : (float) $variant->replacement_cost,
                'reference_id' => $variant->replacement_cost_event_id,
                ...$this->evaluate($cost, (string) ($item['selling_price'] ?? $variant->price), $type, $target === null ? null : (string) $target, $item['sale_factor'] ?? 1),
                'sale_units' => $variant->activeSaleUnits->map(fn ($saleUnit): array => ['id' => $saleUnit->id, 'name' => $saleUnit->name, ...$this->evaluate($cost, (string) ($saleUnit->price ?? 0), $type, $target === null ? null : (string) $target, $saleUnit->conversion_factor)])->all(),
            ];
        }

        return $results;
    }

    public function normalize(string $price, int $factor): string
    {
        if ($factor < 1 || BigDecimal::of($price)->isNegative()) {
            throw new InvalidArgumentException('Invalid purchase price or conversion factor.');
        }

        return (string) BigDecimal::of($price)->dividedBy($factor, 12, RoundingMode::HalfUp);
    }

    /** @return array{cost: float|null, price: float, current_margin: float|null, suggested_price: float|null, below_cost: bool, below_target: bool} */
    public function evaluate(?string $cost, string $price, string $type, ?string $target, int $factor = 1): array
    {
        $selling = BigDecimal::of($price);
        $unitCost = $cost === null ? null : BigDecimal::of($cost)->multipliedBy($factor);
        $suggested = null;
        if ($unitCost !== null && $target !== null) {
            $margin = BigDecimal::of($target);
            if ($margin->isNegative() || ($type === 'percent' && $margin->isGreaterThanOrEqualTo(100))) {
                throw new InvalidArgumentException('Percentage targets must be below 100 and targets cannot be negative.');
            }
            $suggested = $type === 'percent'
                ? $unitCost->dividedBy(BigDecimal::of(1)->minus($margin->dividedBy(100, 12, RoundingMode::HalfUp)), 2, RoundingMode::Ceiling)
                : $unitCost->plus($margin->multipliedBy($factor))->toScale(2, RoundingMode::Ceiling);
        }
        $current = $unitCost !== null && $selling->isPositive()
            ? $selling->minus($unitCost)->dividedBy($selling, 8, RoundingMode::HalfUp)->multipliedBy(100)->toScale(2, RoundingMode::HalfUp)->toFloat()
            : null;

        return [
            'cost' => $unitCost?->toFloat(), 'price' => $selling->toFloat(), 'current_margin' => $current,
            'suggested_price' => $suggested?->toFloat(),
            'below_cost' => $unitCost !== null && $selling->isLessThan($unitCost),
            'below_target' => $suggested !== null && $selling->isLessThan($suggested),
        ];
    }
}
