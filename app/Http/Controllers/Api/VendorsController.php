<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendors\SaveVendorCatalogRequest;
use App\Http\Requests\Api\Vendors\StoreVendorRequest;
use App\Http\Requests\Api\Vendors\UpdateVendorRequest;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\Vendor\VendorCatalogCollection;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\CatalogService;
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

    public function store(StoreVendorRequest $request): JsonResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_CREATE->value, auth()->user());

        $vendor = Vendor::query()->create($request->validated());

        return response()->json($vendor, 201);
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_EDIT->value, auth()->user());

        $vendor->update($request->validated());

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

    public function storeProductVariant(SaveVendorCatalogRequest $request, Vendor $vendor, ProductVariant $variant, CatalogService $service): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_CREATE->value, auth()->user());

        $service->saveVendorOffer($vendor->id, $variant->id, $request->validated('record'));

        return new JsonResponse(['data' => $vendor], 201);
    }

    public function updateProductVariants(SaveVendorCatalogRequest $request, Vendor $vendor, CatalogService $service): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_EDIT->value, auth()->user());

        $service->updateVendorOffers($vendor->id, $request->validated('variants'));

        return response()->json($vendor, 200);
    }

    public function removeProductVariant(Vendor $vendor, ProductVariant $variant, CatalogService $service): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_DELETE->value, auth()->user());

        $service->removeVendorOffers($vendor->id, $variant->id);

        return response()->json(['data' => $vendor], 200);
    }
}
