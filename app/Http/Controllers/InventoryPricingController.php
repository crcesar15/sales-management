<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Inventory\ManagePricingReferenceRequest;
use App\Http\Requests\Inventory\PreviewPricingRequest;
use App\Models\ProductVariant;
use App\Services\PricingRecommendationService;
use App\Services\ReplacementCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class InventoryPricingController extends Controller
{
    public function preview(PreviewPricingRequest $request, PricingRecommendationService $pricing): JsonResponse
    {
        /** @var list<array{product_variant_id: int, unit_id?: int|null, price: float|int|string, margin_type?: string, margin_value?: float|int|string|null, selling_price?: float|int|string, sale_factor?: int}> $items */
        $items = $request->validated('items');
        try {
            return response()->json(['data' => $pricing->preview($items)]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['items' => $exception->getMessage()]);
        }
    }

    public function update(ManagePricingReferenceRequest $request, ProductVariant $variant, ReplacementCostService $service): RedirectResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_EDIT);
        try {
            $service->manage($variant, $request->integer('reference_id'), $request->string('action')->value(), $request->user() ?? abort(401));
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withErrors(['pricing' => $exception->getMessage()]);
        }

        return redirect()->back();
    }
}
