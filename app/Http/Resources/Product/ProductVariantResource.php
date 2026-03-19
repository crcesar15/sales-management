<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'categories' => $this->product?->categories->pluck('name')->join(', '),
            'brand' => $this->product->brand->name ?? null,
            'name' => $this->product->name ?? null,
            'variant' => (($this->product->name ?? null) === ($this->name ?? null))
                ? null
                : $this->name,
            'status' => $this->status,
            'vendors' => $this->vendors ?? [],
        ];
    }
}
