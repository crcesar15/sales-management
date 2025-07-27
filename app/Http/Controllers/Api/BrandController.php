<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Brands\ListBrandRequest;
use App\Http\Requests\Api\Brands\StoreBrandRequest;
use App\Http\Requests\Api\Brands\UpdateBrandRequest;
use App\Http\Resources\ApiCollection;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $brand = DB::transaction(function () use ($validated) {
            return Brand::query()->create($validated);
        });

        return response()->json($brand, 201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $validated = $request->validated();

        $updatedBrand = DB::transaction(function () use ($validated, $brand) {
            return $brand->update($validated);
        });

        return response()->json($updatedBrand, 200);
    }

    public function destroy(Brand $brand): Response
    {
        $this->authorize(PermissionsEnum::BRANDS_DELETE->value, auth()->user());

        DB::transaction(function () use ($brand) {
            $brand->products()->update(['brand_id' => null]);

            $brand->delete();
        });

        return response()->noContent();
    }
}
