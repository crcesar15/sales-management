<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Products as ApiCollection;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ProductsController extends Controller
{
    public function index(Request $request): ApiCollection
    {
        $query = Product::query();

        $filter = $request->string('filter', '')->value();

        if (! empty($filter)) {
            $filter_by = $request->string('filter_by', 'name')->value();

            $filter = '%' . $filter . '%';
            $query->where($filter_by, 'like', $filter);
        }

        $status = $request->string('status', 'all')->value();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $includes = $request->string('includes', '')->value();

        if (! empty($includes)) {
            $query->with(explode(',', $includes));
        }

        $response = $query->orderBy(
            $request->string('order_by', 'name')->value(),
            $request->string('order_direction', 'ASC')->value()
        )->paginate($request->integer('per_page', 10));

        return new ApiCollection($response);
    }

    public function show(Product $product): JsonResponse
    {
        $includes = request()->string('includes', '')->value();

        if (! empty($includes)) {
            $product->with(explode(',', (string) $includes));
        }

        return response()->json($product, 200);
    }

    // Create a new product
    public function store(Request $request): JsonResponse
    {
        // create product
        $product = Product::query()->create([
            'brand_id' => $request->string('brand_id')->value(),
            'measurement_unit_id' => $request->integer('measurement_unit_id'),
            'name' => $request->string('name')->value(),
            'description' => $request->string('description')->value(),
            'options' => json_encode($request->string('options')->value()),
            'status' => $request->string('status')->value(),
        ]);

        // Associate media
        // if ($request->has('media') && count($request->array('media')) > 0) {
        //     /** @var array<array<int>> $medias */
        //     $medias = $request->array('media');

        //     foreach ($medias as $media) {
        //         $media = Media::query()->findOrFail($media['id']);
        //         $media->update([
        //             'model_id' => $product->getKey(),
        //         ]);
        //     }
        // }

        // save categories
        $categories = $request->array('categories');

        if (count($categories) > 0) {
            $product->categories()->attach($categories);
        }

        // save variants
        /**
         * @var array<array<string,int>> $variants
         */
        $variants = $request->array('variants');

        if (count($variants) > 0) {
            foreach ($variants as $variant) {
                $item = [
                    'identifier' => $variant['identifier'],
                    'name' => $variant['name'],
                    'price' => $variant['price'],
                    'stock' => $variant['stock'] ?? 0,
                    'status' => $variant['status'],
                    'media' => json_encode($variant['media'] ?? []),
                ];

                $product->variants()->create($item);
            }
        }

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {

        $product->update([
            'brand_id' => $request->integer('brand_id'),
            'measurement_unit_id' => $request->integer('measurement_unit_id'),
            'name' => $request->string('name')->value(),
            'description' => $request->string('description')->value(),
            'options' => json_encode($request->string('options')->value()),
            'status' => $request->string('status')->value(),
        ]);

        // Associate media
        if ($request->has('media') && count($request->array('media')) > 0) {
            /** @var array<array<int>> $medias */
            $medias = $request->array('media');

            foreach ($medias as $media) {
                $media = Media::query()->findOrFail($media['id']);
                $media->update([
                    'model_id' => $product->getKey(),
                ]);
            }
        }

        // update categories
        $categories = $request->array('categories');

        $product->categories()->sync($categories);

        // update variants
        /**
         * @var array<array<string,int>> $variants
         */
        $variants = $request->array('variants');

        if (count($variants) > 0) {
            foreach ($variants as $variant) {
                $item = [
                    'identifier' => $variant['identifier'],
                    'name' => $variant['name'],
                    'price' => $variant['price'],
                    'stock' => $variant['stock'] ?? 0,
                    'status' => $variant['status'],
                    'media' => json_encode($variant['media'] ?? []),
                ];

                if (isset($variant['id'])) {
                    $product->variants()->findOrFail($variant['id'])->update($item);
                } else {
                    $product->variants()->create($item);
                }
            }

            return response()->json($product, 200);
        }

        return new JsonResponse(['message' => 'Product not found'], 404);
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
