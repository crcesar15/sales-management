<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PurchaseOrdersController extends Controller
{
    // Get all purchase orders
    public function index(Request $request): ApiCollection
    {
        $query = PurchaseOrder::query();

        if ($request->has('include')) {
            $query->with(explode(',', $request->string('include')->value()));
        }

        $status = $request->string('status', 'all')->value();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $filter = $request->string('filter', '')->value();

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        $response = $query->orderBy(
            $request->string('order_by', 'created_at')->value(),
            $request->string('order_direction', 'ASC')->value()
        )->paginate($request->integer('per_page', 10));

        return new ApiCollection($response);
    }

    public function show(PurchaseOrder $order): JsonResponse
    {
        return new JsonResponse($order, 200);
    }

    public function store(Request $request): JsonResponse
    {
        // @phpstan-ignore-next-line
        $order = PurchaseOrder::query()->create($request->all());

        return response()->json($order, 201);
    }

    public function update(Request $request, PurchaseOrder $order): JsonResponse
    {
        // @phpstan-ignore-next-line
        $order->update($request->all());

        return response()->json($order, 200);
    }

    // Delete a brand
    public function destroy(PurchaseOrder $order): Response
    {
        $order->delete();

        return response()->noContent();
    }
}
