<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
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
            'name' => $this->name,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories),
            'measurement_unit' => $this->whenLoaded('measurementUnit', fn () => $this->measurementUnit),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants),
            'stock' => $this->whenLoaded('variants', fn () => $this->variants->sum('stock')),
        ];
    }
}
