<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Brands\ListBrandRequest;
use App\Http\Resources\ApiCollection;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class BrandController extends Controller
{
    public function index(ListBrandRequest $request): ApiCollection
    {
        $request->validated();

        $query = Brand::query();

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

    public function show(Brand $brand): JsonResponse
    {
        $this->authorize(PermissionsEnum::CATEGORIES_VIEW, auth()->user());

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
