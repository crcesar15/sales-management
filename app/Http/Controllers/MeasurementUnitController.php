<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\MeasurementUnits\StoreMeasurementUnitRequest;
use App\Http\Requests\MeasurementUnits\UpdateMeasurementUnitRequest;
use App\Http\Resources\MeasurementUnit\MeasurementUnitCollection;
use App\Models\MeasurementUnit;
use App\Services\MeasurementUnitService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class MeasurementUnitController extends Controller
{
    private readonly MeasurementUnitService $measurementUnitService;

    public function __construct(MeasurementUnitService $measurementUnitService)
    {
        $this->measurementUnitService = $measurementUnitService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::MEASUREMENT_UNITS_VIEW);

        $status = request()->string('status', 'active')->value();

        $measurementUnits = $this->measurementUnitService->list(
            status: $status,
            orderBy: request()->string('order_by', 'name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('MeasurementUnits/Index', [
            'measurementUnits' => new MeasurementUnitCollection($measurementUnits),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function store(StoreMeasurementUnitRequest $request): RedirectResponse
    {
        $this->measurementUnitService->create($request->validated());

        return redirect()->route('measurement-units');
    }

    public function update(UpdateMeasurementUnitRequest $request, MeasurementUnit $measurementUnit): RedirectResponse
    {
        $this->measurementUnitService->update($measurementUnit, $request->validated());

        return redirect()->route('measurement-units');
    }

    public function destroy(MeasurementUnit $measurementUnit): RedirectResponse
    {
        $this->authorize(PermissionsEnum::MEASUREMENT_UNITS_DELETE);

        try {
            $this->measurementUnitService->delete($measurementUnit);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('measurement-units');
    }

    public function restore(MeasurementUnit $measurementUnit): RedirectResponse
    {
        $this->authorize(PermissionsEnum::MEASUREMENT_UNITS_RESTORE);

        $this->measurementUnitService->restore($measurementUnit);

        return redirect()->route('measurement-units');
    }
}
