<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrdersController extends Controller
{
    //Get all purchase orders
    public function index(Request $request)
    {
        $query = PurchaseOrder::query();

        if ($request->has('include')) {
            $query->with(explode(',', $request->get('include')));
        }

        $status = $request->input('status', 'all');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter) {
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

    //Get a brand by id
    public function show(PurchaseOrder $order)
    {
        if ($order) {
            return response()->json(['data' => $order], 200);
        } else {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }
    }

    //Create a new brand
    public function store(Request $request)
    {
        $brand = PurchaseOrder::create($request->all());

        return response()->json(['data' => $brand], 201);
    }

    //Update a brand
    public function update(Request $request, $id)
    {
        $brand = PurchaseOrder::find($id);
        if ($brand) {
            $brand->update($request->all());

            return response()->json(['data' => $brand], 200);
        } else {
            return response()->json(['message' => 'PurchaseOrder not found'], 404);
        }
    }

    //Delete a brand
    public function destroy($id)
    {
        $brand = PurchaseOrder::find($id);
        if ($brand) {
            //remove the brand from all products
            $brand->products()->update(['brand_id' => null]);

            $brand->delete();
        } else {
            return response()->json(['message' => 'PurchaseOrder not found'], 404);
        }
    }
}
