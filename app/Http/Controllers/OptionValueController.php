<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Products\StoreOptionValueRequest;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Services\ProductVariantService;
use Exception;
use Illuminate\Http\RedirectResponse;

final class OptionValueController extends Controller
{
    private ProductVariantService $variantService;

    public function __construct(ProductVariantService $variantService)
    {
        $this->variantService = $variantService;
    }

    public function store(StoreOptionValueRequest $request, Product $product, ProductOption $option): RedirectResponse
    {
        $this->variantService->storeOptionValue($option, $request->validated()['value']);

        return redirect()->route('products.edit', $product);
    }

    public function destroy(Product $product, ProductOption $option, ProductOptionValue $value): RedirectResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_DELETE);

        try {
            $this->variantService->destroyOptionValue($value);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->route('products.edit', $product);
    }
}
