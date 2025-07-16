<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\Variants as VariantsResource;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\VariantService;
use Illuminate\Http\Request;

class VendorsController extends Controller
{
    //Get all vendors
    public function index(Request $request): ApiCollection
    {
        $query = Vendor::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('fullname', 'like', $filter);
                }
            );
        }

        $status = $request->input('status', 'all');

        if ($status !== 'all') {
            $query->where('status', $status);
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

    //Get a vendor by id
    public function show($id): JsonResponse
    {
        $vendor = Vendor::query()->find($id);
        if ($vendor) {
            return new JsonResponse(['data' => $vendor], 200);
        }
        return new JsonResponse(['message' => 'Vendor not found'], 404);
    }

    //Create a new vendor
    public function store(Request $request): JsonResponse
    {
        $vendor = Vendor::query()->create($request->all());

        return new JsonResponse(['data' => $vendor], 201);
    }

    //Update a vendor
    public function update(Request $request, $id): JsonResponse
    {
        $vendor = Vendor::query()->find($id);
        if ($vendor) {
            $vendor->update($request->all());

            return new JsonResponse(['data' => $vendor], 200);
        }
        return new JsonResponse(['message' => 'Vendor not found'], 404);
    }

    //Delete a vendor
    public function destroy($id): JsonResponse
    {
        $vendor = Vendor::query()->find($id);

        if ($vendor) {
            $vendor->delete();

            return new JsonResponse(['data' => $vendor], 200);
        }
        return new JsonResponse(['message' => 'Role not found'], 404);
    }

    public function getProductVariants(Request $request, VariantService $variantService, Vendor $vendor): VariantsResource
    {
        $includes = $request->input('includes', '');
        $includes = explode(',', (string) $includes);

        $page = $request->input('page', 1);
        $per_page = $request->input('per_page', 10);

        $order_by = $request->input('order_by', 'product_name');
        $order_direction = $request->input('order_direction', 'ASC');

        $filter = $request->input('filter', '');
        $filterBy = $request->input('filter_by', 'name');
        $status = $request->input('status', 'all');

        $vendorId = $vendor->id;

        $config = [
            'includes' => $includes,
            'order_by' => $order_by,
            'order_direction' => $order_direction,
            'filter' => $filter,
            'filter_by' => $filterBy,
            'status' => $status,
            'page' => $page,
            'per_page' => $per_page,
            'vendor_id' => $vendorId,
        ];

        // Fetch variants using the service
        $response = $variantService->getVariants($config);

        return new VariantsResource($response);
    }

    public function storeProductVariant(Request $request, Vendor $vendor, ProductVariant $variant): JsonResponse
    {
        $product = $request->input('record');

        if ($vendor && $variant) {
            // Check if the variant already exists for the vendor
            $existingVariant = $vendor->variants()->where('product_variant_id', $variant->id)->first();

            // Remove the existing variant if it exists
            if ($existingVariant) {
                $vendor->variants()->detach($existingVariant->id);
            }

            if (isset($product['previous_product_id'])) {
                // Check if the variant already exists for the vendor
                $existingVariant = $vendor->variants()->where('product_variant_id', $product['previous_product_id'])->first();

                // Remove the existing variant if it exists
                if ($existingVariant) {
                    $vendor->variants()->detach($existingVariant->id);
                }
            }

            $vendor->variants()->attach($variant->id, [
                'price' => $product['price'],
                'details' => $product['details'] ?? null,
                'payment_terms' => $product['payment_terms'],
                'status' => $product['status'] ?? 'active',
            ]);

            return new JsonResponse(['data' => $vendor], 201);
        }
        return new JsonResponse(['message' => 'Vendor or Product not found'], 404);
    }

    public function updateProductVariants(Request $request, Vendor $vendor): \JsonResponse|int
    {
        $products = $request->input('variants');

        $formattedProducts = [];

        foreach ($products as $product) {
            $formattedProducts[$product['id']] = [
                'price' => $product['price'],
                'details' => $product['details'] ?? null,
                'payment_terms' => $product['payment_terms'],
                'status' => $product['status'] ?? 'active',
            ];
        }

        if ($vendor && $product) {
            $vendor->variants()->syncWithoutDetaching($formattedProducts);

            return new JsonResponse(['data' => $vendor], 200);
        }
        return 2;
    }

    public function removeProductVariant(Vendor $vendor, ProductVariant $variant): JsonResponse
    {
        if ($vendor && $variant) {
            $vendor->variants()->detach($variant);

            return new JsonResponse(['data' => $vendor], 200);
        }
        return new JsonResponse(['message' => 'Vendor or Product not found'], 404);
    }
}
