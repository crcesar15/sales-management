<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Products as ApiCollection;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    //Get all products
    public function index(Request $request): ApiCollection
    {
        $query = Product::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter_by = $request->input('filter_by', 'name');

            $filter = '%' . $filter . '%';
            $query->where($filter_by, 'like', $filter);
        }

        $status = $request->input('status', 'all');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $includes = $request->input('includes', '');

        if (!empty($includes)) {
            $query->with(explode(',', (string) $includes));
        }

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'name';
        $order_direction = $request->has('order_direction')
            ? $request->get('order_direction')
            : 'ASC';

        $response = $query->orderBy(
            $request->input('order_by', $order_by),
            $request->input('order_direction', $order_direction)
        )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    //Get a product by id
    public function show($id): JsonResponse
    {
        $product = Product::query();

        $includes = request()->input('includes', '');

        if (!empty($includes)) {
            $product->with(explode(',', (string) $includes));
        }

        $product = $product->find($id);

        if ($product) {
            return new JsonResponse(['data' => $product], 200);
        }
        return new JsonResponse(['message' => 'Product not found'], 404);
    }

    //Create a new product
    public function store(Request $request): JsonResponse
    {
        // create product
        $product = Product::query()->create([
            'brand_id' => $request->input('brand_id'),
            'measure_unit_id' => $request->input('measure_unit_id'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'options' => json_encode($request->input('options')),
            'status' => $request->input('status'),
        ]);

        // Associate media
        if ($request->has('media') && count($request->input('media')) > 0) {
            foreach ($request->input('media') as $media) {
                Media::query()->find($media['id'])->update([
                    'model_id' => $product->id,
                ]);
            }
        }

        // save categories
        $categories = $request->input('categories', []);

        if (count($categories) > 0) {
            $product->categories()->attach($categories);
        }

        // save variants
        $variants = $request->input('variants', []);

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

        return new JsonResponse(['data' => $product], 201);
    }

    //Update a product
    public function update(Request $request, $id): JsonResponse
    {
        $product = Product::query()->find($id);
        if ($product) {
            $product->update([
                'brand_id' => $request->input('brand_id'),
                'measure_unit_id' => $request->input('measure_unit_id'),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'options' => json_encode($request->input('options')),
                'status' => $request->input('status'),
            ]);

            // Associate media
            if ($request->has('media') && count($request->input('media')) > 0) {
                foreach ($request->input('media') as $media) {
                    Media::query()->find($media['id'])->update([
                        'model_id' => $product->id,
                    ]);
                }
            }

            // update categories
            $categories = $request->input('categories', []);

            $product->categories()->sync($categories);

            // update variants
            $variants = $request->input('variants', []);

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

                    if (isset($variant['id']) && $variant['id'] !== null) {
                        $product->variants()->find($variant['id'])->update($item);
                    } else {
                        $product->variants()->create($item);
                    }
                }
            }

            return new JsonResponse(['data' => $product], 200);
        }
        return new JsonResponse(['message' => 'Product not found'], 404);
    }

    //Delete a product
    public function destroy($id): JsonResponse
    {
        $product = Product::query()->find($id);
        if ($product) {
            $product->delete();

            return new JsonResponse(['data' => $product], 200);
        }
        return new JsonResponse(['message' => 'Product not found'], 404);
    }
}
