<?php

declare(strict_types=1);

namespace App\Http\Resources\ProductGallery;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin ProductVariant */
final class ProductGalleryVariantResource extends JsonResource
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
            'brand_name' => $product?->brand?->name,
            'option_values' => $variant->values
                ->map(fn ($value) => $value->value)
                ->values()
                ->toArray(),
            'price' => (float) $variant->price,
            'unit_name' => $product?->measurementUnit?->name,
            'is_available' => (bool) $variant->getAttribute('is_available'),
            'thumbnail_url' => $this->thumbnailUrl($variant),
        ];
    }

    private function thumbnailUrl(ProductVariant $variant): ?string
    {
        $media = $variant->images->first();

        if (! $media instanceof Media) {
            $media = $variant->product?->getMedia('images')->first();
        }

        return $media instanceof Media ? $media->getAvailableUrl(['thumb']) : null;
    }
}
