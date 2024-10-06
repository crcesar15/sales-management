<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SuppliersController extends Controller
{
    //Get all suppliers
    public function index(Request $request)
    {
        $query = Supplier::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter) {
                    $query->where('fullname', 'like', $filter);
                }
            );
        }

        $status = $request->input('status', 'all');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'name';
        $order_direction = $request->has('order_direction')
            ? $request->get('order_direction')
            : 'ASC';

        $response = $query->orderBy(
            $request->input('order_by', $order_by),
            $request->input('order_direction', $order_direction)
        )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    //Get a supplier by id
    public function show($id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            return response()->json(['data' => $supplier], 200);
        } else {
            return response()->json(['message' => 'Supplier not found'], 404);
        }
    }

    //Create a new supplier
    public function store(Request $request)
    {
        $supplier = Supplier::create($request->all());

        return response()->json(['data' => $supplier], 201);
    }

    //Update a supplier
    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $supplier->update($request->all());

            return response()->json(['data' => $supplier], 200);
        } else {
            return response()->json(['message' => 'Supplier not found'], 404);
        }
    }

    //Delete a supplier
    public function destroy($id)
    {
        $supplier = Supplier::find($id);

        if ($supplier) {
            $supplier->delete();

            return response()->json(['data' => $supplier], 200);
        } else {
            return response()->json(['message' => 'Role not found'], 404);
        }
    }
}
