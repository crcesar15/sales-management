<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use Inertia\Inertia;
use Inertia\Response;

final class MeasurementUnitController extends Controller
{
    public function index(): Response
    {
        $this->authorize(PermissionsEnum::MEASUREMENT_UNITS_VIEW, auth()->user());

        return Inertia::render('MeasurementUnits/Index');
    }
}
