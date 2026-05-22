<?php

declare(strict_types=1);

namespace App\Http\Resources\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Catalog */
final class VendorCatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->productVariant;
        $purchaseUnit = $this->unit;

        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'vendor_id' => $this->vendor_id,
            'price' => (float) $this->price,
            'payment_terms' => $this->payment_terms,
            'details' => $this->details,
            'status' => $this->status,
            'unit_id' => $this->unit_id,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'lead_time_days' => $this->lead_time_days,
            'product_variant' => $variant ? [
                'id' => $variant->id,
                'identifier' => $variant->identifier,
                'barcode' => $variant->barcode,
                'price' => (float) $variant->price,
                'stock' => (int) ($variant->batch_stock ?? 0),
                'minimum_stock_level' => $variant->minimum_stock_level,
                'status' => $variant->status,
                'name' => $variant->name,
                'product' => $variant->relationLoaded('product') && $variant->product ? [
                    'id' => $variant->product->id,
                    'name' => $variant->product->name,
                    'brand' => $variant->product->relationLoaded('brand') && $variant->product->brand ? [
                        'id' => $variant->product->brand->id,
                        'name' => $variant->product->brand->name,
                    ] : null,
                    'measurement_unit' => $variant->product->relationLoaded('measurementUnit') && $variant->product->measurementUnit ? [
                        'id' => $variant->product->measurementUnit->id,
                        'name' => $variant->product->measurementUnit->name,
                        'abbreviation' => $variant->product->measurementUnit->abbreviation,
                    ] : null,
                ] : null,
                'values' => $variant->relationLoaded('values') ? $variant->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'option_name' => $v->option?->name,
                ]) : [],
                'purchase_units' => $variant->relationLoaded('activePurchaseUnits') ? $variant->activePurchaseUnits->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'conversion_factor' => $u->conversion_factor,
                ]) : [],
            ] : null,
            'purchase_unit' => $purchaseUnit ? [
                'id' => $purchaseUnit->id,
                'name' => $purchaseUnit->name,
                'conversion_factor' => $purchaseUnit->conversion_factor,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
