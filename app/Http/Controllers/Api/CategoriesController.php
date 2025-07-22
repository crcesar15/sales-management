<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CategoriesController extends Controller
{
    public function index(Request $request): ApiCollection
    {
        $query = Category::query();

        $filter = $request->string('filter', '')->value();

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        $query->withCount('products');

        $response = $query->orderBy(
            $request->string('order_by', 'name')->value(),
            $request->string('order_direction', 'ASC')->value()
        )->paginate($request->integer('per_page', 10));

        return new ApiCollection($response);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category, 200);
    }

    public function store(Request $request): JsonResponse
    {
        // @phpstan-ignore-next-line
        $category = Category::query()->create($request->all());

        return response()->json(['data' => $category], 201);
    }

    // Update a category
    public function update(Request $request, Category $category): JsonResponse
    {
        // @phpstan-ignore-next-line
        $category->update($request->all());

        return new JsonResponse($category, 200);
    }

    public function destroy(Category $category): Response
    {
        // remove the category from the intermediate table
        $category->products()->detach();

        $category->delete();

        return response()->noContent();
    }
}
