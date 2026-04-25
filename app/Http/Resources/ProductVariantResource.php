<?php

declare(strict_types=1);

namespace App\Http\Resources;

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
            'purchase_units' => $this->whenLoaded('purchaseUnits') ? $this->purchaseUnits->map(fn ($u) => [
                'id' => $u->id,
                'type' => $u->type,
                'name' => $u->name,
                'conversion_factor' => $u->conversion_factor,
                'price' => $u->price,
                'status' => $u->status,
                'sort_order' => $u->sort_order,
            ]) : [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
