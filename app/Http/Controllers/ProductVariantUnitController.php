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
use InvalidArgumentException;

final class ProductVariantUnitController extends Controller
{
    public function __construct(
        private readonly ProductVariantUnitService $unitService
    ) {}

    public function store(StoreProductVariantUnitRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        try {
            $this->unitService->create(
                $variant,
                $request->safe()->except('replicate_to_siblings'),
                $request->boolean('replicate_to_siblings'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withErrors(['replicate_to_siblings' => $exception->getMessage()]);
        }

        return redirect()->back();
    }

    public function update(UpdateProductVariantUnitRequest $request, Product $product, ProductVariant $variant, ProductVariantUnit $unit): RedirectResponse
    {
        try {
            $this->unitService->update(
                $unit,
                $request->safe()->except('replicate_to_siblings'),
                $request->boolean('replicate_to_siblings'),
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withErrors(['replicate_to_siblings' => $exception->getMessage()]);
        }

        return redirect()->back();
    }

    public function destroy(Product $product, ProductVariant $variant, ProductVariantUnit $unit): RedirectResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_EDIT);

        $this->unitService->delete($unit);

        return redirect()->back();
    }
}
