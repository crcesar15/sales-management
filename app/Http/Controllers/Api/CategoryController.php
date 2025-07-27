<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Categories\ListCategoryRequest;
use App\Http\Resources\ApiCollection;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CategoryController extends Controller
{
    public function index(ListCategoryRequest $request): ApiCollection
    {
        $request->validated();

        $query = Category::query();

        if ($request->has('filter')) {
            $query->where('name', 'like', $request->string('filter')->value());
        }

        $query->withCount('products');

        $query->orderBy(
            $request->string('order_by')->value(),
            $request->string('order_direction')->value()
        );

        $response = $query->paginate($request->integer('per_page'));

        return new ApiCollection($response);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATEGORY_VIEW, auth()->user());

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
