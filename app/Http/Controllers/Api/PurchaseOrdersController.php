<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseOrders\StorePurchaseOrderRequest;
use App\Http\Requests\Api\PurchaseOrders\UpdatePurchaseOrderRequest;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\PurchaseOrder\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PurchaseOrdersController extends Controller
{
    // Get all purchase orders
    public function index(Request $request): ApiCollection
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_VIEW->value, auth()->user());

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
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_VIEW->value, auth()->user());

        return (new PurchaseOrderResource($order))->response()->setStatusCode(200);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_CREATE->value, auth()->user());

        $order = PurchaseOrder::query()->create($request->validated());

        return (new PurchaseOrderResource($order))->response()->setStatusCode(201);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $order): JsonResponse
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_EDIT->value, auth()->user());

        $order->update($request->validated());

        return (new PurchaseOrderResource($order))->response()->setStatusCode(200);
    }

    // Delete a purchase order
    public function destroy(PurchaseOrder $order): Response
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_EDIT->value, auth()->user());

        $order->delete();

        return response()->noContent();
    }
}
