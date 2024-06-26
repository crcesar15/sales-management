<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Products as ApiCollection;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    //Get all products
    public function index(Request $request)
    {
        $query = Product::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter_by = $request->input('filter_by', 'name');

            $filter = '%' . $filter . '%';
            $query->where($filter_by, 'like', $filter);
        }

        $status = $request->input('status', 'all');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $includes = $request->input('includes', '');

        if (!empty($includes)) {
            $query->with(explode(',', $includes));
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

    //Get a product by id
    public function show($id)
    {
        $product = Product::query();

        $includes = request()->input('includes', '');

        if (!empty($includes)) {
            $product->with(explode(',', $includes));
        }

        $product = $product->find($id);

        if ($product) {
            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Create a new product
    public function store(Request $request)
    {
        $product = Product::create($request->all());

        return response()->json(['data' => $product], 201);
    }

    //Update a product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->update($request->all());

            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Delete a product
    public function destroy($id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->delete();

            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }
}
