<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin ProductVariant */
final class VariantSearchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductVariant $variant */
        $variant = $this->resource;

        $product = $variant->product;

        return [
            'id' => $variant->id,
            'identifier' => $variant->identifier,
            'barcode' => $variant->barcode,
            'option_values' => $variant->values->isNotEmpty()
                ? $variant->values->map(fn ($value) => $value->value)->implode(' / ')
                : null,
            'variant_options' => $variant->values
                ->map(fn ($value) => [
                    'option_name' => $value->option?->name,
                    'value' => $value->value,
                ])
                ->values()
                ->toArray(),
            'price' => (float) $variant->price,
            'stock' => $this->resolveStock($variant),
            'minimum_stock_level' => $variant->minimum_stock_level,
            'has_expiration' => $variant->has_expiration,
            'product' => $product !== null ? [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand !== null ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'measurement_unit' => $product->measurementUnit !== null ? [
                    'id' => $product->measurementUnit->id,
                    'name' => $product->measurementUnit->name,
                ] : null,
            ] : null,
            'sale_units' => $this->when(
                $variant->relationLoaded('activeSaleUnits') && $variant->activeSaleUnits->isNotEmpty(),
                fn () => $variant->activeSaleUnits->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'conversion_factor' => $unit->conversion_factor,
                    'price' => (float) $unit->price,
                ])->values()->toArray(),
            ),
            'purchase_units' => $this->when(
                $variant->relationLoaded('activePurchaseUnits') && $variant->activePurchaseUnits->isNotEmpty(),
                fn () => $variant->activePurchaseUnits->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'conversion_factor' => $unit->conversion_factor,
                ])->values()->toArray(),
            ),
            'thumbnail_url' => $this->when(
                $variant->relationLoaded('images'),
                fn () => $this->resolveThumbnailUrl($variant),
            ),
        ];
    }

    /**
     * Store-scoped searches carry a `fulfillable_stock` aggregate attribute
     * (null when the store has no batches); its absence means the aggregate
     * `stock` column applies.
     */
    private function resolveStock(ProductVariant $variant): int
    {
        $attributes = $variant->getAttributes();

        if (array_key_exists('fulfillable_stock', $attributes)) {
            return (int) ($attributes['fulfillable_stock'] ?? 0);
        }

        return (int) $variant->stock;
    }

    /**
     * First assigned variant image (by pivot position), then the first
     * product image (by order column), then null.
     */
    private function resolveThumbnailUrl(ProductVariant $variant): ?string
    {
        $media = $variant->images->first();

        $product = $variant->product;

        if ($media === null && $product !== null && $product->relationLoaded('media')) {
            $productMedia = $product->getMedia('images')->first();
            $media = $productMedia instanceof Media ? $productMedia : null;
        }

        return $media?->getAvailableUrl(['thumb']);
    }
}
