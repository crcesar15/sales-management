<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Catalog */
final class CatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'product_variant_id' => $this->product_variant_id,
            'unit_id' => $this->unit_id,
            'price' => (float) $this->price,
            'payment_terms' => $this->payment_terms,
            'details' => $this->details,
            'status' => $this->status,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'lead_time_days' => $this->lead_time_days,
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor?->id,
                'fullname' => $this->vendor?->fullname,
            ]),
            'product_variant' => $this->whenLoaded('productVariant', function () {
                $variant = $this->productVariant;
                $product = $variant?->product;

                return [
                    'id' => $variant?->id,
                    'name' => $variant?->name,
                    'identifier' => $variant?->identifier,
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'brand' => $product->brand ? [
                            'id' => $product->brand->id,
                            'name' => $product->brand->name,
                        ] : null,
                        'measurement_unit' => $product->measurementUnit ? [
                            'id' => $product->measurementUnit->id,
                            'name' => $product->measurementUnit->name,
                            'abbreviation' => $product->measurementUnit->abbreviation,
                        ] : null,
                    ] : null,
                    'values' => $variant?->values->map(fn ($value) => [
                        'option_name' => $value->option?->name,
                        'value' => $value->value,
                    ])->toArray() ?? [],
                    'purchase_units' => $variant?->activePurchaseUnits->map(fn ($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                    ])->toArray() ?? [],
                ];
            }),
            'purchase_unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit?->id,
                'name' => $this->unit?->name,
                'conversion_factor' => $this->unit?->conversion_factor,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
