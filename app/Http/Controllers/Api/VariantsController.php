<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\VariantVendorResource;
use App\Http\Resources\Product\ProductVariantCollection;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\VariantService;
use Carbon\Carbon;
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

    public function search(Request $request): JsonResponse
    {
        $filter = $request->string('filter', '')->value();
        $includes = $request->string('includes', '')->value();
        $includeList = array_filter(explode(',', $includes));

        $query = ProductVariant::query()
            ->with(['product.brand', 'activeSaleUnits', 'values.option', ...in_array('saleUnits', $includeList) ? ['activeSaleUnits'] : []])
            ->where('status', '!=', 'archived')
            ->where(function ($q) use ($filter): void {
                $q->whereHas('product', function ($sq) use ($filter): void {
                    $sq->where('name', 'like', "%{$filter}%");
                })
                    ->orWhere('identifier', 'like', "%{$filter}%");
            });

        $variants = $query->orderBy('identifier')
            ->limit(20)
            ->get()
            ->map(function ($variant) {
                $productName = $variant->product !== null ? $variant->product->name : '';
                $brandName = $variant->product?->brand?->name;

                $optionValues = $variant->values->isNotEmpty()
                    ? $variant->values->map(fn ($v) => $v->value)->implode(' / ')
                    : null;

                $variantLabel = $variant->identifier ?: $optionValues;

                $data = [
                    'id' => $variant->id,
                    'name' => $productName,
                    'identifier' => $variant->identifier,
                    'variant_label' => $variantLabel,
                    'option_values' => $optionValues,
                    'label' => $productName . ' - ' . $variantLabel,
                    'price' => (float) $variant->price,
                    'stock' => $variant->stock,
                    'minimum_stock_level' => $variant->minimum_stock_level,
                    'product' => $variant->product ? [
                        'id' => $variant->product->id,
                        'name' => $variant->product->name,
                        'brand' => $brandName ? ['id' => $variant->product->brand->id, 'name' => $brandName] : null,
                        'measurement_unit' => $variant->product->measurementUnit ? [
                            'id' => $variant->product->measurementUnit->id,
                            'name' => $variant->product->measurementUnit->name,
                        ] : null,
                    ] : null,
                ];

                if ($variant->relationLoaded('activeSaleUnits') && $variant->activeSaleUnits->isNotEmpty()) {
                    $data['sale_units'] = $variant->activeSaleUnits->map(fn ($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'conversion_factor' => $unit->conversion_factor,
                        'price' => (float) $unit->price,
                    ])->values()->toArray();
                }

                return $data;
            });

        return response()->json(['data' => $variants], 200);
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

    public function purchasePriceHistory(ProductVariant $variant): JsonResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW, auth()->user());

        $items = $variant->purchaseOrderItems()
            ->with(['purchaseOrder.vendor'])
            ->whereHas('purchaseOrder', function ($query): void {
                $query->whereNotIn('status', ['draft', 'cancelled']);
            })
            ->orderByDesc(
                PurchaseOrder::select('order_date')
                    ->whereColumn('purchase_orders.id', 'purchase_order_product.purchase_order_id')
            )
            ->get();

        $history = $items->map(function ($item) {
            $po = $item->purchaseOrder;
            $orderDate = $po instanceof PurchaseOrder ? $po->order_date : null;

            return [
                'date' => $orderDate !== null ? Carbon::parse((string) $orderDate)->format('Y-m-d') : null,
                'price' => (float) $item->price,
                'po_id' => $po instanceof PurchaseOrder ? $po->id : null,
                'vendor_name' => $po instanceof PurchaseOrder && $po->vendor instanceof Vendor ? $po->vendor->fullname : null,
            ];
        });

        $latest = $history->first();

        $prices = $history->pluck('price')->filter(fn ($p) => $p !== null);

        return response()->json([
            'data' => [
                'latest_purchase_price' => $latest ? $latest['price'] : null,
                'latest_po_date' => $latest ? $latest['date'] : null,
                'latest_vendor_name' => $latest ? $latest['vendor_name'] : null,
                'history' => $history->toArray(),
                'stats' => [
                    'latest' => $prices->count() > 0 ? round((float) $prices->first(), 2) : null,
                    'average' => $prices->count() > 0 ? round((float) $prices->avg(), 2) : null,
                    'highest' => $prices->count() > 0 ? round((float) $prices->max(), 2) : null,
                    'lowest' => $prices->count() > 0 ? round((float) $prices->min(), 2) : null,
                ],
            ],
        ], 200);
    }
}
