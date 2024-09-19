<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
    //Get all brands
    public function index(Request $request)
    {
        $query = Brand::query();

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

    //Get a brand by id
    public function show($id)
    {
        $product = Brand::find($id);
        if ($product) {
            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Create a new brand
    public function store(Request $request)
    {
        $product = Brand::create($request->all());

        return response()->json(['data' => $product], 201);
    }

    //Update a brand
    public function update(Request $request, $id)
    {
        $product = Brand::find($id);
        if ($product) {
            $product->update($request->all());

            return response()->json(['data' => $product], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Delete a brand
    public function destroy($id)
    {
        $brand = Brand::find($id);
        if ($brand) {
            //remove the brand from all products
            $brand->products()->update(['brand_id' => null]);

            $brand->delete();
        } else {
            return response()->json(['message' => 'Brand not found'], 404);
        }
    }
}
