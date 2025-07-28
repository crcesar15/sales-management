<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MeasurementUnits\ListMeasurementUnitRequest;
use App\Http\Requests\Api\MeasurementUnits\StoreMeasurementUnitRequest;
use App\Http\Requests\Api\MeasurementUnits\UpdateMeasurementUnitRequest;
use App\Http\Resources\ApiCollection;
use App\Models\MeasurementUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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

    public function store(StoreMeasurementUnitRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $measurementUnit = DB::transaction(function () use ($validated) {
            return MeasurementUnit::query()->create($validated);
        });

        return response()->json($measurementUnit, 201);
    }

    public function update(UpdateMeasurementUnitRequest $request, MeasurementUnit $measurementUnit): JsonResponse
    {
        $validated = $request->validated();

        $updatedMeasurementUnit = DB::transaction(function () use ($measurementUnit, $validated) {
            return $measurementUnit->update($validated);
        });

        return response()->json($updatedMeasurementUnit, 200);
    }

    public function destroy(MeasurementUnit $measureUnit): Response
    {
        // remove the measurement unit from all products
        $measureUnit->products()->update(['measurement_unit_id' => null]);

        $measureUnit->delete();

        return response()->noContent();
    }
}
