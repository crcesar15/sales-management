<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductVariant */
final class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'identifier' => $this->identifier,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'status' => $this->status,
            'name' => $this->name,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'brand' => $this->product?->brand ? [
                    'id' => $this->product->brand->id,
                    'name' => $this->product->brand->name,
                ] : null,
                'measurement_unit' => $this->product?->measurementUnit ? [
                    'id' => $this->product->measurementUnit->id,
                    'name' => $this->product->measurementUnit->name,
                    'abbreviation' => $this->product->measurementUnit->abbreviation,
                ] : null,
                'categories' => $this->product?->categories?->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ]),
            ]),
            'values' => $this->whenLoaded('values') ? $this->values->map(fn ($v) => [
                'id' => $v->id,
                'value' => $v->value,
                'option_name' => $v->option?->name,
            ]) : [],
            'images' => $this->whenLoaded('images') ? $this->images->map(fn ($img) => [
                'id' => $img->id,
                'thumb_url' => $img->getUrl('thumb'),
                'full_url' => $img->getUrl(),
            ]) : [],
            'sale_units' => $this->whenLoaded('saleUnits') ? $this->saleUnits->map(fn ($u) => [
                'id' => $u->id,
                'type' => $u->type,
                'name' => $u->name,
                'conversion_factor' => $u->conversion_factor,
                'price' => (float) $u->price,
                'status' => $u->status,
                'sort_order' => $u->sort_order,
            ]) : [],
            'purchase_units' => $this->whenLoaded('activePurchaseUnits') ? $this->activePurchaseUnits->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'conversion_factor' => $u->conversion_factor,
            ]) : [],
            'pivot' => $this->when(
                $this->relationLoaded('vendors') && $this->vendors->isNotEmpty(),
                function () {
                    $vendorPivot = $this->vendors->first()->pivot;
                    $purchaseUnit = $vendorPivot->unit_id
                        ? $this->activePurchaseUnits?->first(fn ($u) => $u->id === $vendorPivot->unit_id)
                        : null;

                    return [
                        'price' => (float) $vendorPivot->price,
                        'payment_terms' => $vendorPivot->payment_terms,
                        'details' => $vendorPivot->details,
                        'status' => $vendorPivot->status,
                        'unit_id' => $vendorPivot->unit_id,
                        'minimum_order_quantity' => $vendorPivot->minimum_order_quantity,
                        'lead_time_days' => $vendorPivot->lead_time_days,
                        'purchase_unit' => $purchaseUnit ? [
                            'id' => $purchaseUnit->id,
                            'name' => $purchaseUnit->name,
                            'conversion_factor' => $purchaseUnit->conversion_factor,
                        ] : null,
                    ];
                }
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
