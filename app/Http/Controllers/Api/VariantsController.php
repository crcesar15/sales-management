<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Variants as ApiCollection;
use App\Models\Media;
use App\Models\ProductVariant;
use App\Services\VariantService;
use DebugBar;
use Illuminate\Http\Request;

class VariantsController extends Controller
{
    //Get all variants
    public function index(Request $request, VariantService $variantService)
    {
        $includes = $request->input('includes', '');
        $includes = explode(',', $includes);

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        $order_by = $request->input('order_by', 'product_name');
        $order_direction = $request->input('order_direction', 'ASC');

        $filter = $request->input('filter', '');
        $filterBy = $request->input('filter_by', 'name');
        $status = $request->input('status', 'all');

        $config = [
            'includes' => $includes,
            'order_by' => $order_by,
            'order_direction' => $order_direction,
            'filter' => $filter,
            'filter_by' => $filterBy,
            'status' => $status,
            'page' => $page,
            'per_page' => $per_page,
        ];

        // Fetch variants using the service
        $response = $variantService->getVariants($config);

        return new ApiCollection($response);
    }

    //Get a product by id
    public function show($id)
    {
        $product = Product::query();

        $includes = request()->input('includes', '');

        if (!empty($includes)) {
            $product->with(explode(',', $includes));
        }

        $product = $product->find($id);

        if ($product) {
            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Create a new product
    public function store(Request $request)
    {
        // create product
        $product = Product::create([
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
                Media::find($media['id'])->update([
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

        return response()->json(['data' => $product], 201);
    }

    //Update a product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
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
                    Media::find($media['id'])->update([
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

            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Delete a product
    public function destroy($id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->delete();

            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    // Get vendors
    public function getVendors($id)
    {
        $variant = ProductVariant::find($id);

        $vendors = $variant->vendors;

        return response()->json(['data' => $vendors], 200);
    }

    // Update variant vendor
    public function updateVendors(Request $request, $id)
    {
        $variant = ProductVariant::find($id);

        $vendors = $request->input('vendors', []);

        $variant->vendors()->detach();

        if (count($vendors) > 0) {
            foreach ($vendors as $vendor) {
                $variant->vendors()->attach($vendor['id'], [
                    'price' => $vendor['price'],
                    'payment_terms' => $vendor['payment_terms'],
                    'details' => $vendor['details'],
                ]);
            }
        }

        return response()->json(['data' => $variant], 200);
    }
}
