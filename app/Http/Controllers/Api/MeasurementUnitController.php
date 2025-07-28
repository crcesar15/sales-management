<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MeasurementUnits\ListMeasurementUnitRequest;
use App\Http\Resources\ApiCollection;
use App\Models\MeasurementUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MeasurementUnitController extends Controller
{
    public function index(ListMeasurementUnitRequest $request): ApiCollection
    {
        $request->validated();

        $query = MeasurementUnit::query();

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

    public function show(MeasurementUnit $measureUnit): JsonResponse
    {
        $this->authorize(PermissionsEnum::MEASUREMENT_UNITS_VIEW, auth()->user());

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
