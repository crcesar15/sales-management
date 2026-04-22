<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Resources\Inventory\InventoryCollection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class InventoryController extends Controller
{
    public readonly VariantService $variantService;

    public function __construct(VariantService $variantService)
    {
        $this->variantService = $variantService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW, auth()->user());

        $variants = $this->variantService->listAllVariants(
            status: request()->string('status', 'all')->toString(),
            filter: request()->string('filter')->toString(),
            orderBy: request()->string('order_by', 'created_at')->toString(),
            orderDirection: request()->string('order_direction', 'desc')->toString(),
            perPage: request()->integer('per_page', 15),
        );

        return Inertia::render('Inventory/Index', [
            'variants' => new InventoryCollection($variants),
            'filters' => [
                'status' => request()->string('status', 'all')->toString(),
                'filter' => request()->string('filter')->toString(),
            ],
        ]);
    }

    public function show(Product $product, ProductVariant $variant): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_EDIT, auth()->user());

        $variant->load([
            'values.option',
            'images',
            'product.brand',
            'product.categories',
            'product.measurementUnit',
            'product.media',
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
                'created_at' => $variant->created_at?->toISOString(),
                'updated_at' => $variant->updated_at?->toISOString(),
            ],
        ]);
    }
}
