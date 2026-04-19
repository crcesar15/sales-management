<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Resources\Inventory\InventoryCollection;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class InventoryController extends Controller
{
    private readonly VariantService $variantService;

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

        return Inertia::render('Inventory/Variants/Index', [
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

        return Inertia::render('Inventory/Variants/Show/Index', [
            'product' => new ProductResource($variant->product),
            'variant' => new ProductVariantResource($variant),
        ]);
    }
}
