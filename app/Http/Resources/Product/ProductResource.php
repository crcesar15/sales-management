<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'brand' => $this->whenLoaded('brand') && $this->brand !== null ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ] : null,
            'categories' => $this->whenLoaded('categories') ? $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ]) : [],
            'measurement_unit' => $this->whenLoaded('measurementUnit') && $this->measurementUnit !== null ? [
                'id' => $this->measurementUnit->id,
                'name' => $this->measurementUnit->name,
            ] : null,
            'media' => $this->getMedia('images')->map(fn ($m) => [
                'id' => $m->id,
                'thumb_url' => $m->getUrl('thumb'),
                'full_url' => $m->getUrl(),
            ]),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
