<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
    //Get all brands
    public function index(Request $request): ApiCollection
    {
        $query = Brand::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
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

    //Get a brand by id
    public function show($id): JsonResponse
    {
        $brand = Brand::query()->find($id);
        if ($brand) {
            return new JsonResponse(['data' => $brand], 200);
        }
        return new JsonResponse(['message' => 'Brand not found'], 404);
    }

    //Create a new brand
    public function store(Request $request): JsonResponse
    {
        $brand = Brand::query()->create($request->all());

        return new JsonResponse(['data' => $brand], 201);
    }

    //Update a brand
    public function update(Request $request, $id): JsonResponse
    {
        $brand = Brand::query()->find($id);
        if ($brand) {
            $brand->update($request->all());

            return new JsonResponse(['data' => $brand], 200);
        }
        return new JsonResponse(['message' => 'Brand not found'], 404);
    }

    //Delete a brand
    public function destroy($id): ?\JsonResponse
    {
        $brand = Brand::query()->find($id);
        if ($brand) {
            //remove the brand from all products
            $brand->products()->update(['brand_id' => null]);

            $brand->delete();
        } else {
            return new JsonResponse(['message' => 'Brand not found'], 404);
        }
        return null;
    }
}
