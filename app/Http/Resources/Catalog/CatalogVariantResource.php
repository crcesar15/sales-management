<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductVariant */
final class CatalogVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'status' => $this->status,
            'name' => $this->name,
            'product' => $this->whenLoaded('product') ? (function () {
                $product = $this->product;

                return [
                    'id' => $product?->id,
                    'name' => $product?->name,
                    'brand' => $product?->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                    ] : null,
                    'measurement_unit' => $product?->measurementUnit ? [
                        'id' => $product->measurementUnit->id,
                        'name' => $product->measurementUnit->name,
                        'abbreviation' => $product->measurementUnit->abbreviation,
                    ] : null,
                ];
            })() : null,
            'values' => $this->whenLoaded('values')
                ? $this->values->map(fn ($v) => [
                    'option_name' => $v->option?->name,
                    'value' => $v->value,
                ])->toArray()
                : [],
            'purchase_units' => $this->whenLoaded('activePurchaseUnits')
                ? $this->activePurchaseUnits->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                ])->toArray()
                : [],
            'vendor_count' => $this->when(isset($this->vendor_count), $this->vendor_count ?? 0),
            'vendors' => $this->whenLoaded('catalogEntries')
                ? $this->catalogEntries->map(fn ($entry) => [
                    'id' => $entry->id,
                    'vendor' => $entry->vendor ? [
                        'id' => $entry->vendor->id,
                        'fullname' => $entry->vendor->fullname,
                    ] : null,
                    'price' => (float) $entry->price,
                    'unit' => $entry->unit ? [
                        'id' => $entry->unit->id,
                        'name' => $entry->unit->name,
                        'conversion_factor' => $entry->unit->conversion_factor,
                    ] : null,
                    'payment_terms' => $entry->payment_terms,
                    'minimum_order_quantity' => $entry->minimum_order_quantity,
                    'lead_time_days' => $entry->lead_time_days,
                    'status' => $entry->status,
                ])->toArray()
                : [],
        ];
    }
}
