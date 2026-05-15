<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\Vendor\VendorCatalogCollection;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\VariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class VendorsController extends Controller
{
    public function __construct(private readonly VariantService $variantService) {}

    public function index(Request $request): ApiCollection
    {
        $this->authorize(PermissionsEnum::VENDORS_VIEW, auth()->user());

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
        $this->authorize(PermissionsEnum::VENDORS_VIEW, auth()->user());

        return response()->json($vendor, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_CREATE, auth()->user());

        // TODO Develop fromRequest
        // @phpstan-ignore-next-line
        $vendor = Vendor::query()->create($request->all());

        return response()->json($vendor, 201);
    }

    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_EDIT, auth()->user());

        // TODO Develop formRequest
        // @phpstan-ignore-next-line
        $vendor->update($request->all());

        return response()->json($vendor, 200);
    }

    public function destroy(Vendor $vendor): Response
    {
        $this->authorize(PermissionsEnum::VENDORS_DELETE, auth()->user());

        $vendor->delete();

        return response()->noContent();
    }

    public function getProductVariants(
        Request $request,
        Vendor $vendor
    ): VendorCatalogCollection {
        $this->authorize(PermissionsEnum::CATALOG_VIEW, auth()->user());

        $catalog = $this->variantService->getVendorCatalog(
            vendorId: $vendor->id,
            status: $request->string('status', 'active')->value(),
            filter: $request->filled('filter') ? $request->string('filter')->value() : null,
            orderBy: $request->string('order_by', 'product_name')->value(),
            orderDirection: $request->string('order_direction', 'ASC')->value(),
            page: $request->integer('page', 1),
            perPage: $request->integer('per_page', 10),
        );

        return new VendorCatalogCollection($catalog);
    }

    public function storeProductVariant(Request $request, Vendor $vendor, ProductVariant $variant): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_CREATE, auth()->user());

        $product = $request->array('record');

        // If replacing a previous variant, detach it first
        if (isset($product['previous_product_id'])) {
            $vendor->variants()->where('product_variant_id', $product['previous_product_id'])->detach();
        }

        // Detach existing entry for this variant if present, then attach fresh
        $vendor->variants()->where('product_variant_id', $variant->id)->detach();

        $vendor->variants()->attach($variant->id, [
            'price' => $product['price'],
            'details' => $product['details'] ?? null,
            'payment_terms' => $product['payment_terms'],
            'unit_id' => $product['unit_id'] ?? null,
            'minimum_order_quantity' => $product['minimum_order_quantity'] ?? null,
            'lead_time_days' => $product['lead_time_days'] ?? null,
            'status' => $product['status'] ?? 'active',
        ]);

        return new JsonResponse(['data' => $vendor], 201);
    }

    public function updateProductVariants(Request $request, Vendor $vendor): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_EDIT, auth()->user());

        /** @var array<array<string,number>>$products */
        $products = $request->array('variants');

        $formattedProducts = [];

        foreach ($products as $product) {
            $formattedProducts[$product['id']] = [
                'price' => $product['price'],
                'details' => $product['details'] ?? null,
                'payment_terms' => $product['payment_terms'],
                'unit_id' => $product['unit_id'] ?? null,
                'minimum_order_quantity' => $product['minimum_order_quantity'] ?? null,
                'lead_time_days' => $product['lead_time_days'] ?? null,
                'status' => $product['status'] ?? 'active',
            ];
        }

        $vendor->variants()->syncWithoutDetaching($formattedProducts);

        return response()->json($vendor, 200);
    }

    public function removeProductVariant(Vendor $vendor, ProductVariant $variant): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_DELETE, auth()->user());

        $vendor->variants()->detach($variant);

        return response()->json(['data' => $vendor], 200);
    }
}
