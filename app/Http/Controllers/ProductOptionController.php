<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Products\StoreProductOptionRequest;
use App\Models\Product;
use App\Models\ProductOption;
use App\Services\ProductVariantService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductOptionController extends Controller
{
    private ProductVariantService $variantService;

    public function __construct(ProductVariantService $variantService)
    {
        $this->variantService = $variantService;
    }

    public function store(StoreProductOptionRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->variantService->storeOption($product, $request->validated());
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->route('products.edit', $product);
    }

    public function update(Request $request, Product $product, ProductOption $option): RedirectResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_EDIT);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        $this->variantService->updateOption($option, $validated['name']);

        return redirect()->route('products.edit', $product);
    }

    public function destroy(Product $product, ProductOption $option): RedirectResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_DELETE);

        try {
            $this->variantService->destroyOption($option);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->route('products.edit', $product);
    }
}
