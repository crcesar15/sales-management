<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Products\GenerateVariantsRequest;
use App\Http\Requests\Products\StoreVariantRequest;
use App\Http\Requests\Products\SyncVariantImagesRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

final class ProductVariantController extends Controller
{
    private ProductVariantService $variantService;

    public function __construct(ProductVariantService $variantService)
    {
        $this->variantService = $variantService;
    }

    public function generate(GenerateVariantsRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->variantService->generateVariants($product, $request->validated()['options']);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->route('products.edit', $product);
    }

    public function store(StoreVariantRequest $request, Product $product): RedirectResponse
    {
        try {
            $variant = $this->variantService->storeManual($product, $request->validated());
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        if (auth()->user()?->can(PermissionsEnum::INVENTORY_VIEW->value) ?? false) {
            return redirect()->route('inventory.variants.show', $variant);
        }

        return redirect()->route('products.edit', $product);
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_DELETE);

        $this->variantService->destroy($variant);

        return redirect()->route('products.edit', $product);
    }

    public function syncImages(SyncVariantImagesRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->variantService->syncVariantImages($variant, $request->validated()['media_ids']);

        return redirect()->back();
    }
}
