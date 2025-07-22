<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\Variants as VariantsResource;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\VariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class VendorsController extends Controller
{
    public function index(Request $request): ApiCollection
    {
        $query = Vendor::query();

        $filter = $request->string('filter', '')->value();

        if ($request->has('filter')) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('fullname', 'like', $filter);
                }
            );
        }

        $status = $request->string('status', 'all')->value();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $response = $query->orderBy(
            $request->string('order_by', 'name')->value(),
            $request->string('order_direction', 'ASC')->value()
        )->paginate($request->integer('per_page', 10));

        return new ApiCollection($response);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return response()->json($vendor, 200);
    }

    public function store(Request $request): JsonResponse
    {
        // TODO Develop fromRequest
        // @phpstan-ignore-next-line
        $vendor = Vendor::query()->create($request->all());

        return response()->json($vendor, 201);
    }

    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        // TODO Develop formRequest
        // @phpstan-ignore-next-line
        $vendor->update($request->all());

        return response()->json($vendor, 200);
    }

    public function destroy(Vendor $vendor): Response
    {
        $vendor->delete();

        return response()->noContent();
    }

    public function getProductVariants(
        Request $request,
        VariantService $variantService,
        Vendor $vendor
    ): VariantsResource {
        // TODO: Develop formRequest
        $includes = $request->string('includes', '')->value();
        $includes = explode(',', $includes);

        $page = $request->integer('page', 1);
        $per_page = $request->integer('per_page', 10);

        $order_by = $request->string('order_by', 'product_name')->value();
        $order_direction = $request->string('order_direction', 'ASC')->value();

        $filter = $request->string('filter', '')->value();
        $filterBy = $request->string('filter_by', 'name')->value();
        $status = $request->string('status', 'all')->value();

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
        $product = $request->array('record');

        // Check if the variant already exists for the vendor
        /** @var ProductVariant $existingVariant */
        $existingVariant = $vendor->variants()->where('product_variant_id', $variant->id)->first();

        // Remove the existing variant if it exists
        $vendor->variants()->detach($existingVariant->id);

        // TODO: Refactor logic
        if (isset($product['previous_product_id'])) {
            // Check if the variant already exists for the vendor
            /** @var ProductVariant $existingVariant */
            $existingVariant = $vendor->variants()
                ->where('product_variant_id', $product['previous_product_id'])
                ->first();

            $vendor->variants()->detach($existingVariant->id);
        }

        $vendor->variants()->attach($variant->id, [
            'price' => $product['price'],
            'details' => $product['details'] ?? null,
            'payment_terms' => $product['payment_terms'],
            'status' => $product['status'] ?? 'active',
        ]);

        return new JsonResponse(['data' => $vendor], 201);
    }

    public function updateProductVariants(Request $request, Vendor $vendor): JsonResponse
    {
        /** @var array<array<string,number>>$products */
        $products = $request->array('variants');

        $formattedProducts = [];

        foreach ($products as $product) {
            $formattedProducts[$product['id']] = [
                'price' => $product['price'],
                'details' => $product['details'] ?? null,
                'payment_terms' => $product['payment_terms'],
                'status' => $product['status'] ?? 'active',
            ];
        }

        $vendor->variants()->syncWithoutDetaching($formattedProducts);

        return response()->json($vendor, 200);
    }

    public function removeProductVariant(Vendor $vendor, ProductVariant $variant): JsonResponse
    {
        $vendor->variants()->detach($variant);

        return response()->json(['data' => $vendor], 200);
    }
}
