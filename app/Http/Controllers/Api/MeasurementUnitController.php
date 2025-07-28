<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\MeasurementUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MeasurementUnitController extends Controller
{
    public function index(Request $request): ApiCollection
    {
        $query = MeasurementUnit::query();

        $filter = $request->string('filter', '')->value();

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        $response = $query->orderBy(
            $request->string('order_by', 'name')->value(),
            $request->string('order_direction', 'ASC')->value()
        )->paginate($request->integer('per_page', 10));

        return new ApiCollection($response);
    }

    public function show(MeasurementUnit $measureUnit): JsonResponse
    {
        return response()->json($measureUnit, 200);
    }

    public function store(Request $request): JsonResponse
    {
        // @phpstan-ignore-next-line
        $measureUnit = MeasurementUnit::query()->create($request->all());

        return response()->json($measureUnit, 201);
    }

    public function update(Request $request, MeasurementUnit $unit): JsonResponse
    {
        // @phpstan-ignore-next-line
        $unit->update($request->all());

        return response()->json($unit, 200);
    }

    public function destroy(MeasurementUnit $measureUnit): Response
    {
        // remove the measurement unit from all products
        $measureUnit->products()->update(['measurement_unit_id' => null]);

        $measureUnit->delete();

        return response()->noContent();
    }
}
