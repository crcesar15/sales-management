<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoriesController extends Controller
{
    // Get all products
    public function index(Request $request): ApiCollection
    {
        $query = Category::query();

        $filter = $request->input('filter', '');

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
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

    // Get a category by id
    public function show($id): JsonResponse
    {
        $category = Category::query()->find($id);
        if ($category) {
            return new JsonResponse(['data' => $category], 200);
        }

        return new JsonResponse(['message' => 'Category not found'], 404);
    }

    // Create a new category
    public function store(Request $request): JsonResponse
    {
        $category = Category::query()->create($request->all());

        return new JsonResponse(['data' => $category], 201);
    }

    // Update a category
    public function update(Request $request, $id): JsonResponse
    {
        $category = Category::query()->find($id);
        if ($category) {
            $category->update($request->all());

            return new JsonResponse(['data' => $category], 200);
        }

        return new JsonResponse(['message' => 'Category not found'], 404);
    }

    // Delete a category
    public function destroy($id): JsonResponse
    {
        $category = Category::query()->find($id);
        if ($category) {
            // remove the category from the intermediate table
            $category->products()->detach();

            $category->delete();

            return new JsonResponse(['data' => $category], 200);
        }

        return new JsonResponse(['message' => 'Category not found'], 404);
    }
}
