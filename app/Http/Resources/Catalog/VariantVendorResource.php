<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Catalog */
final class VariantVendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor?->id,
                'fullname' => $this->vendor?->fullname,
            ]),
            'price' => (float) $this->price,
            'unit' => $this->whenLoaded('unit', fn () => $this->unit ? [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'conversion_factor' => $this->unit->conversion_factor,
            ] : null),
            'payment_terms' => $this->payment_terms,
            'measurement_unit' => $this->whenLoaded('productVariant', fn () => $this->productVariant?->product?->measurementUnit ? [
                'id' => $this->productVariant->product->measurementUnit->id,
                'name' => $this->productVariant->product->measurementUnit->name,
            ] : null),
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'lead_time_days' => $this->lead_time_days,
            'status' => $this->status,
        ];
    }
}
