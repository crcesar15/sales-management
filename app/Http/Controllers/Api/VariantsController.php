<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Variants\UpdateVariantVendorsRequest;
use App\Http\Resources\Catalog\VariantVendorResource;
use App\Http\Resources\Product\ProductVariantCollection;
use App\Http\Resources\Product\VariantSearchCollection;
use App\Models\ProductVariant;
use App\Services\CatalogService;
use App\Services\PurchasePriceHistoryService;
use App\Services\VariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VariantsController extends Controller
{
    public function index(Request $request, VariantService $variantService): ProductVariantCollection
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

        return new ProductVariantCollection($response);
    }

    public function show(ProductVariant $variant): JsonResponse
    {
        $includes = request()->string('includes', '')->value();

        if (! empty($includes)) {
            $variant->with(explode(',', (string) $includes));
        }

        return response()->json($variant, 200);
    }

    public function getVendors(ProductVariant $variant): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_VIEW, auth()->user());

        $variant->load(['catalogEntries.vendor', 'catalogEntries.unit', 'catalogEntries.productVariant.product.measurementUnit']);

        $vendors = $variant->catalogEntries->map(fn ($entry) => new VariantVendorResource($entry));

        return response()->json(['data' => $vendors], 200);
    }

    // Update product variant vendors
    public function updateVendors(UpdateVariantVendorsRequest $request, ProductVariant $variant, CatalogService $service): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_EDIT->value, $request->user());
        /** @var list<array{id: int, price: float|int|string, payment_terms?: string|null, details?: string|null}> $vendors */
        $vendors = $request->validated('vendors');
        $service->syncVariantVendors($variant, $vendors);

        return response()->json(['data' => $variant], 200);
    }

    public function search(Request $request, VariantService $variantService): JsonResponse
    {
        $includes = array_filter(explode(',', $request->string('includes', '')->value()));
        if (in_array('location', $includes, true)) {
            $this->authorize(PermissionsEnum::POS_ACCESS->value, $request->user());
            abort_unless($request->integer('store_id') > 0, 422);
            app(\App\Services\ReportingScopeService::class)->storeIds($request->user() ?? abort(401), $request->integer('store_id'));
        }
        $variants = $variantService->search([
            'filter' => $request->string('filter', '')->value(),
            'includes' => $includes,
            'store_id' => $request->integer('store_id', 0),
        ]);

        return (new VariantSearchCollection($variants))->response()->setStatusCode(200);
    }

    public function purchaseUnits(ProductVariant $variant): JsonResponse
    {
        $units = $variant->activePurchaseUnits()
            ->get()
            ->map(fn ($unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'conversion_factor' => $unit->conversion_factor,
            ]);

        return response()->json(['data' => $units], 200);
    }

    public function purchasePriceHistory(ProductVariant $variant, PurchasePriceHistoryService $historyService): JsonResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW->value, auth()->user());

        return response()->json(['data' => $historyService->history($variant)]);
    }
}
