<?php

declare(strict_types=1);

namespace App\Http\Resources\ProductGallery;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin ProductVariant */
final class ProductGalleryVariantDetailResource extends JsonResource
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
            'product_name' => $product?->name,
            'description' => $product?->description,
            'brand_name' => $product?->brand?->name,
            'identifier' => $variant->identifier,
            'barcode' => $variant->barcode,
            'option_values' => $variant->values
                ->map(fn ($value) => [
                    'option_name' => $value->option?->name,
                    'value' => $value->value,
                ])
                ->values()
                ->toArray(),
            'categories' => $product?->categories
                ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])
                ->values()
                ->toArray() ?? [],
            'price' => (float) $variant->price,
            'unit_name' => $product?->measurementUnit?->name,
            'is_available' => (bool) $variant->getAttribute('is_available'),
            'images' => $this->images($variant),
            'sale_units' => $variant->activeSaleUnits
                ->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'conversion_factor' => (float) $unit->conversion_factor,
                    'price' => (float) $unit->price,
                ])
                ->values()
                ->toArray(),
        ];
    }

    /**
     * @return array<int, array{id: int, thumb_url: string, full_url: string}>
     */
    private function images(ProductVariant $variant): array
    {
        $images = $variant->images->isNotEmpty() ? $variant->images : $variant->product?->getMedia('images');

        return ($images ?? collect())
            ->filter(fn ($media) => $media instanceof Media)
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'thumb_url' => $media->getAvailableUrl(['thumb']),
                'full_url' => $media->getUrl(),
            ])
            ->values()
            ->all();
    }
}
