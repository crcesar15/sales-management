<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class Products extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        /** @var ResourceCollection<Product> $products */
        $products = $this->collection;

        foreach ($products as $product) {
            $formattedProduct = [
                'id' => $product->id,
                'name' => $product->name,
                'status' => $product->status,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'options' => $product->options,
            ];

            if ($request->has('include') && in_array('brand', explode(',', $request->string('include')->value()))) {
                $formattedProduct['brand'] = $product->brand;
            }

            if (
                $request->has('include')
                    && in_array('categories', explode(',', $request->string('include')->value()))
            ) {
                $formattedProduct['categories'] = $product->categories;
            }

            if (
                $request->has('include')
                    && in_array('measurementUnit', explode(',', $request->string('include')->value()))
            ) {
                $formattedProduct['measurement_unit'] = $product->measurementUnit;
            }

            if (
                $request->has('include')
                    && (
                        in_array('variants', explode(',', $request->string('include')->value()))
                            || in_array('variants.media', explode(',', $request->string('include')->value()))
                    )
            ) {
                $formattedProduct['variants'] = $product->variants;
                $formattedProduct['stock'] = $product->variants->sum('stock');
            }

            $data[] = $formattedProduct;
        }

        return [
            'data' => $data,
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
