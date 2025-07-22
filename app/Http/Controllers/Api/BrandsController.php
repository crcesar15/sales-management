<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BrandsController extends Controller
{
    public function index(Request $request): ApiCollection
    {
        $query = Brand::query();

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

    public function show(Brand $brand): JsonResponse
    {
        return response()->json($brand, 200);
    }

    public function store(Request $request): JsonResponse
    {
        // @phpstan-ignore-next-line
        $brand = Brand::query()->create($request->all());

        return response()->json($brand, 201);
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        // @phpstan-ignore-next-line
        $brand->update($request->all());

        return response()->json($brand, 200);
    }

    public function destroy(Brand $brand): Response
    {
        $brand->products()->update(['brand_id' => null]);

        $brand->delete();

        return response()->noContent();
    }
}
