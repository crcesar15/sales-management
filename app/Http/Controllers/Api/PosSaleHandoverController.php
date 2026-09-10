<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Pos\PreviewPosSaleHandoverRequest;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;

final class PosSaleHandoverController extends Controller
{
    public function __construct(private readonly SalesOrderService $salesOrderService) {}

    public function preview(PreviewPosSaleHandoverRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS->value, auth()->user());

        try {
            $preview = $this->salesOrderService->previewPosFulfillment(
                $salesOrder,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $preview]);
    }
}
