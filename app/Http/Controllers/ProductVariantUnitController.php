<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Products\StoreProductVariantUnitRequest;
use App\Http\Requests\Products\UpdateProductVariantUnitRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Services\ProductVariantUnitService;
use Illuminate\Http\RedirectResponse;

final class ProductVariantUnitController extends Controller
{
    public function __construct(
        private readonly ProductVariantUnitService $unitService
    ) {}

    public function store(StoreProductVariantUnitRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->unitService->create($variant, $request->validated());

        return redirect()->back();
    }

    public function update(UpdateProductVariantUnitRequest $request, Product $product, ProductVariant $variant, ProductVariantUnit $unit): RedirectResponse
    {
        $this->unitService->update($unit, $request->validated());

        return redirect()->back();
    }

    public function destroy(Product $product, ProductVariant $variant, ProductVariantUnit $unit): RedirectResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_EDIT);

        $this->unitService->delete($unit);

        return redirect()->back();
    }
}
