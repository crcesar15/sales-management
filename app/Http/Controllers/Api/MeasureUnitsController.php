<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\MeasureUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeasureUnitsController extends Controller
{
    // Get all the measure units
    public function index(Request $request): ApiCollection
    {
        $query = MeasureUnit::query();

        $filter = $request->input('filter', '');

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
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

    // Get a measure unit by id
    public function show($id): JsonResponse
    {
        $measureUnit = MeasureUnit::query()->find($id);
        if ($measureUnit) {
            return new JsonResponse(['data' => $measureUnit], 200);
        }

        return new JsonResponse(['message' => 'Measure unit not found'], 404);
    }

    // Create a new measure unit
    public function store(Request $request): JsonResponse
    {
        $measureUnit = MeasureUnit::query()->create($request->all());

        return new JsonResponse(['data' => $measureUnit], 201);
    }

    // Update a measure unit
    public function update(Request $request, $id): JsonResponse
    {
        $measureUnit = MeasureUnit::query()->find($id);
        if ($measureUnit) {
            $measureUnit->update($request->all());

            return new JsonResponse(['data' => $measureUnit], 200);
        }

        return new JsonResponse(['message' => 'Measure unit not found'], 404);
    }

    // Delete a measure unit
    public function destroy($id): ?\JsonResponse
    {
        $measureUnit = MeasureUnit::query()->find($id);
        if ($measureUnit) {
            // remove the measure unit from all products
            $measureUnit->products()->update(['measure_unit_id' => null]);

            $measureUnit->delete();
        } else {
            return new JsonResponse(['message' => 'Measure unit not found'], 404);
        }

        return null;
    }
}
