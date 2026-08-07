<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SalesOrders\PreviewSalesOrderHandoverRequest;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;

final class SalesOrderHandoverController extends Controller
{
    public function __construct(private readonly SalesOrderService $salesOrderService) {}

    public function preview(PreviewSalesOrderHandoverRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize(PermissionsEnum::SALES_MANAGE->value, auth()->user());

        try {
            $preview = $this->salesOrderService->previewFulfillment($salesOrder, $request->user() ?? throw new RuntimeException('Unauthenticated.'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $preview]);
    }
}
