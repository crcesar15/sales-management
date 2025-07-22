<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Variants as ApiCollection;
use App\Models\ProductVariant;
use App\Services\VariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VariantsController extends Controller
{
    public function index(Request $request, VariantService $variantService): ApiCollection
    {
        $includes = $request->string('includes', '')->value();
        $includes = explode(',', $includes);

        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);

        $order_by = $request->string('order_by', 'product_name')->value();
        $order_direction = $request->string('order_direction', 'ASC')->value();

        $filter = $request->string('filter', '')->value();
        $filterBy = $request->string('filter_by', 'name')->value();
        $status = $request->string('status', 'all')->value();

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

    public function show(ProductVariant $variant): JsonResponse
    {
        $includes = request()->string('includes', '')->value();

        if (! empty($includes)) {
            $variant->with(explode(',', (string) $includes));
        }

        return response()->json($variant, 200);
    }

    // Get vendors by product variant
    public function getVendors(ProductVariant $variant): JsonResponse
    {
        $vendors = $variant->vendors;

        // TODO: Develop resource
        return response()->json($vendors, 200);
    }

    // Update product variant vendors
    public function updateVendors(Request $request, ProductVariant $variant): JsonResponse
    {
        // TODO: Develop formRequest
        /** @var array<array<string,int>> $vendors */
        $vendors = $request->array('vendors');

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
