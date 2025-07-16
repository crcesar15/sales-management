<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PurchaseOrdersController extends Controller
{
    // Get all purchase orders
    public function index(Request $request): ApiCollection
    {
        $query = PurchaseOrder::query();

        if ($request->has('include')) {
            $query->with(explode(',', (string) $request->get('include')));
        }

        $status = $request->input('status', 'all');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $filter = $request->input('filter', '');

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'created_at';
        $order_direction = $request->has('order_direction')
            ? $request->get('order_direction')
            : 'ASC';

        $response = $query->orderBy(
            $request->input('order_by', $order_by),
            $request->input('order_direction', $order_direction)
        )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    // Get a brand by id
    public function show(PurchaseOrder $order): JsonResponse
    {
        if ($order) {
            return new JsonResponse(['data' => $order], 200);
        }

        return new JsonResponse(['message' => 'Purchase order not found'], 404);
    }

    // Create a new brand
    public function store(Request $request): JsonResponse
    {
        $brand = PurchaseOrder::query()->create($request->all());

        return new JsonResponse(['data' => $brand], 201);
    }

    // Update a brand
    public function update(Request $request, $id): JsonResponse
    {
        $brand = PurchaseOrder::query()->find($id);
        if ($brand) {
            $brand->update($request->all());

            return new JsonResponse(['data' => $brand], 200);
        }

        return new JsonResponse(['message' => 'PurchaseOrder not found'], 404);
    }

    // Delete a brand
    public function destroy($id): ?\JsonResponse
    {
        $brand = PurchaseOrder::query()->find($id);
        if ($brand) {
            // remove the brand from all products
            $brand->products()->update(['brand_id' => null]);

            $brand->delete();
        } else {
            return new JsonResponse(['message' => 'PurchaseOrder not found'], 404);
        }

        return null;
    }
}
