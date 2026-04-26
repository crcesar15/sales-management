<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\StockOverviewCollection;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\StockService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class StockOverviewController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW);

        $variants = $this->stockService->listStockOverview(
            storeId: request()->integer('store_id') ?: null,
            categoryId: request()->integer('category_id') ?: null,
            brandId: request()->integer('brand_id') ?: null,
            lowStockOnly: request()->boolean('low_stock'),
            search: request()->string('search', '')->toString(),
            orderBy: request()->string('order_by', 'product_name')->toString(),
            orderDirection: request()->string('order_direction', 'asc')->toString(),
            perPage: request()->integer('per_page', 15),
        );

        return Inertia::render('Inventory/Stock/Index', [
            'variants' => new StockOverviewCollection($variants),
            'filters' => [
                'store_id' => request()->integer('store_id') ?: null,
                'category_id' => request()->integer('category_id') ?: null,
                'brand_id' => request()->integer('brand_id') ?: null,
                'low_stock' => request()->boolean('low_stock'),
                'search' => request()->string('search', '')->toString(),
                'order_by' => request()->string('order_by', 'product_name')->toString(),
                'order_direction' => request()->string('order_direction', 'asc')->toString(),
            ],
            'stores' => Store::query()->where('status', 'active')->get(['id', 'name', 'code']),
            'categories' => Category::query()->get(['id', 'name']),
            'brands' => Brand::query()->get(['id', 'name']),
        ]);
    }

    public function show(ProductVariant $variant): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW);

        $breakdown = $this->stockService->getVariantStockBreakdown($variant);
        $variant = $breakdown['variant'];
        $totalQuantity = $breakdown['total_quantity'];

        return Inertia::render('Inventory/Stock/Show', [
            'stockDetail' => [
                'variant' => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product?->name,
                    'brand_name' => $variant->product?->brand?->name,
                    'name' => $variant->name,
                    'identifier' => $variant->identifier,
                    'barcode' => $variant->barcode,
                    'price' => (float) $variant->price,
                    'status' => $variant->status,
                    'total_stock' => $totalQuantity,
                    'is_low_stock' => $totalQuantity <= 0,
                    'values' => $variant->values->map(fn ($v) => [
                        'id' => $v->id,
                        'value' => $v->value,
                        'option_name' => $v->option?->name,
                    ]),
                ],
                'stores' => $breakdown['stores']->map(fn ($row) => [
                    'store_id' => $row['store']?->id,
                    'store_name' => $row['store']?->name,
                    'store_code' => $row['store']?->code,
                    'quantity' => $row['quantity'],
                ]),
            ],
        ]);
    }
}
