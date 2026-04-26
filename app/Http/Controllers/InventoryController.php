<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Resources\Inventory\StockOverviewCollection;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\StockService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class InventoryController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW, auth()->user());

        $variants = $this->stockService->listStockOverview(
            storeId: request()->integer('store_id') ?: null,
            categoryId: request()->integer('category_id') ?: null,
            brandId: request()->integer('brand_id') ?: null,
            lowStockOnly: request()->boolean('low_stock'),
            search: request()->string('search', '')->toString(),
            orderBy: request()->string('order_by', 'product_name')->toString(),
            orderDirection: request()->string('order_direction', 'asc')->toString(),
            perPage: request()->integer('per_page', 15),
            status: request()->string('status', 'active')->toString(),
        );

        return Inertia::render('Inventory/Index', [
            'variants' => new StockOverviewCollection($variants),
            'filters' => [
                'store_id' => request()->integer('store_id') ?: null,
                'category_id' => request()->integer('category_id') ?: null,
                'brand_id' => request()->integer('brand_id') ?: null,
                'low_stock' => request()->boolean('low_stock'),
                'search' => request()->string('search', '')->toString(),
                'order_by' => request()->string('order_by', 'product_name')->toString(),
                'order_direction' => request()->string('order_direction', 'asc')->toString(),
                'status' => request()->string('status', 'active')->toString(),
            ],
            'stores' => Store::query()->where('status', 'active')->get(['id', 'name', 'code']),
            'categories' => Category::query()->get(['id', 'name']),
            'brands' => Brand::query()->get(['id', 'name']),
        ]);
    }

    public function show(ProductVariant $variant): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_EDIT, auth());

        $variant->load([
            'values.option',
            'images',
            'product.brand',
            'product.categories',
            'product.measurementUnit',
            'product.media',
            'saleUnits',
            'purchaseUnits',
        ]);

        return Inertia::render('Inventory/Show/Index', [
            'product' => [
                'id' => $variant->product->id,
                'name' => $variant->product->name,
                'description' => $variant->product->description,
                'status' => $variant->product->status,
                'brand' => $variant->product->brand ? [
                    'id' => $variant->product->brand->id,
                    'name' => $variant->product->brand->name,
                ] : null,
                'categories' => $variant->product->categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ]),
                'measurement_unit' => $variant->product->measurementUnit ? [
                    'id' => $variant->product->measurementUnit->id,
                    'name' => $variant->product->measurementUnit->name,
                    'abbreviation' => $variant->product->measurementUnit->abbreviation,
                ] : null,
                'media' => $variant->product->getMedia('images')->map(fn ($m) => [
                    'id' => $m->id,
                    'thumb_url' => $m->getUrl('thumb'),
                    'full_url' => $m->getUrl(),
                ]),
                'deleted_at' => $variant->product->deleted_at?->toISOString(),
                'created_at' => $variant->product->created_at?->toISOString(),
            ],
            'variant' => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'identifier' => $variant->identifier,
                'barcode' => $variant->barcode,
                'price' => (float) $variant->price,
                'stock' => $variant->stock,
                'status' => $variant->status,
                'name' => $variant->name,
                'values' => $variant->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'option_name' => $v->option?->name,
                ]),
                'images' => $variant->images->map(fn ($img) => [
                    'id' => $img->id,
                    'thumb_url' => $img->getUrl('thumb'),
                    'full_url' => $img->getUrl(),
                ]),
                'sale_units' => $variant->saleUnits->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'conversion_factor' => $u->conversion_factor,
                    'price' => (float) $u->price,
                    'status' => $u->status,
                    'sort_order' => $u->sort_order,
                    'type' => $u->type,
                ]),
                'purchase_units' => $variant->purchaseUnits->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'conversion_factor' => $u->conversion_factor,
                    'price' => $u->price,
                    'status' => $u->status,
                    'sort_order' => $u->sort_order,
                    'type' => $u->type,
                ]),
                'created_at' => $variant->created_at?->toISOString(),
                'updated_at' => $variant->updated_at?->toISOString(),
            ],
        ]);
    }
}
