<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    //Get all products
    public function index(Request $request)
    {
        $query = Category::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter) {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        $query->withCount('products');

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

    //Get a category by id
    public function show($id)
    {
        $category = Category::find($id);
        if ($category) {
            return response()->json(['data' => $category], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Create a new category
    public function store(Request $request)
    {
        $category = Category::create($request->all());

        return response()->json(['data' => $category], 201);
    }

    //Update a category
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if ($category) {
            $category->update($request->all());

            return response()->json(['data' => $category], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    //Delete a category
    public function destroy($id)
    {
        $category = Category::find($id);
        if ($category) {
            // remove the category from the intermediate table
            $category->products()->detach();

            $category->delete();

            return response()->json(['data' => $category], 200);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }
}
