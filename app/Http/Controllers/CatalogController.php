<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Catalog\StoreCatalogRequest;
use App\Http\Requests\Catalog\UpdateCatalogRequest;
use App\Http\Resources\Catalog\CatalogCollection;
use App\Http\Resources\Catalog\CatalogResource;
use App\Http\Resources\Catalog\CatalogVariantCollection;
use App\Http\Resources\Product\ProductVariantResource;
use App\Models\Catalog;
use App\Models\ProductVariant;
use App\Models\Vendor;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CatalogController extends Controller
{
    public function __construct(private readonly CatalogService $catalogService) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_VIEW);

        $status = request()->string('status', 'active')->value();
        $vendorId = request()->integer('vendor_id') ?: null;

        $variants = $this->catalogService->listVariants(
            status: $status,
            orderBy: request()->string('sort_field', 'product_name')->value(),
            orderDirection: request()->string('sort_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 10),
            filter: request()->string('filter')->value() ?: null,
            vendorId: $vendorId,
        );

        return Inertia::render('Catalog/Index', [
            'variants' => new CatalogVariantCollection($variants),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'sort_field' => request()->string('sort_field', 'product_name')->value(),
                'sort_direction' => request()->string('sort_direction', 'asc')->value(),
                'vendor_id' => $vendorId,
            ],
            'vendors' => Vendor::where('status', 'active')
                ->orderBy('fullname')
                ->get(['id', 'fullname']),
        ]);
    }

    public function show(ProductVariant $productVariant): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_VIEW);

        $variant = $this->catalogService->getVariantWithCatalogEntries($productVariant->id);

        return Inertia::render('Catalog/Show/Index', [
            'productVariant' => (new ProductVariantResource($variant))->resolve(),
            'catalogEntries' => CatalogResource::collection($variant->catalogEntries)->resolve(),
        ]);
    }

    // Vendor-scoped catalog methods

    public function vendorIndex(Vendor $vendor): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_VIEW);

        $status = request()->string('status', 'active')->value();

        $catalog = $this->catalogService->list(
            status: $status,
            orderBy: request()->string('order_by', 'created_at')->value(),
            orderDirection: request()->string('order_direction', 'desc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
            vendorId: $vendor->id,
        );

        return Inertia::render('Vendors/Catalog/Index', [
            'vendor' => $vendor,
            'catalog' => new CatalogCollection($catalog),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'created_at')->value(),
                'order_direction' => request()->string('order_direction', 'desc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function vendorCreate(Vendor $vendor): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_CREATE);

        return Inertia::render('Vendors/Catalog/Create/Index', [
            'vendor' => $vendor,
        ]);
    }

    public function vendorStore(Vendor $vendor, StoreCatalogRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['vendor_id'] = $vendor->id;

        $this->catalogService->create($data);

        return redirect()->route('vendors.catalog', $vendor);
    }

    public function vendorEdit(Vendor $vendor, Catalog $catalog): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_EDIT);

        $catalog->load(['vendor', 'productVariant.product', 'purchaseUnit']);

        return Inertia::render('Vendors/Catalog/Edit/Index', [
            'vendor' => $vendor,
            'catalog' => $catalog,
        ]);
    }

    public function vendorUpdate(Vendor $vendor, Catalog $catalog, UpdateCatalogRequest $request): RedirectResponse
    {
        $this->catalogService->update($catalog, $request->validated());

        return redirect()->route('vendors.catalog', $vendor);
    }

    public function vendorDestroy(Vendor $vendor, Catalog $catalog): RedirectResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_DELETE);

        try {
            $this->catalogService->delete($catalog);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('vendors.catalog', $vendor);
    }
}
