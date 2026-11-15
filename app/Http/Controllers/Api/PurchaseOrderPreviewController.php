<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseOrders\PreviewPurchaseOrderRequest;
use App\Services\PricingRecommendationService;
use App\Services\PurchaseOrderTotalsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class PurchaseOrderPreviewController extends Controller
{
    public function __invoke(PreviewPurchaseOrderRequest $request, PurchaseOrderTotalsService $calculator, PricingRecommendationService $pricing): JsonResponse
    {
        $permission = $request->user()?->can(PermissionsEnum::PURCHASE_ORDERS_CREATE->value) ? PermissionsEnum::PURCHASE_ORDERS_CREATE : PermissionsEnum::PURCHASE_ORDERS_EDIT;
        $this->authorize($permission->value, $request->user());
        try {
            return response()->json(['data' => $calculator->preview($request->validated(), $pricing)]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['items' => $exception->getMessage()]);
        }
    }
}
