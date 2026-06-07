<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\CashRegisterShifts\CloseShiftRequest;
use App\Http\Requests\CashRegisterShifts\ForceCloseShiftRequest;
use App\Http\Requests\CashRegisterShifts\OpenShiftRequest;
use App\Http\Resources\CashRegisterShift\CashRegisterShiftCollection;
use App\Http\Resources\CashRegisterShift\CashRegisterShiftResource;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Services\CashRegisterShiftService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CashRegisterShiftController extends Controller
{
    private readonly CashRegisterShiftService $shiftService;

    public function __construct(CashRegisterShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SHIFTS_VIEW);

        $filters = [
            'cash_register_id' => request()->integer('cash_register_id') ?: null,
            'user_id' => request()->integer('user_id') ?: null,
            'status' => request()->string('status', 'all')->value(),
            'date_from' => request()->string('date_from')->value() ?: null,
            'date_to' => request()->string('date_to')->value() ?: null,
        ];

        $shifts = $this->shiftService->list(
            filters: $filters,
            perPage: request()->integer('per_page', 20),
        );

        return Inertia::render('CashRegisterShifts/Index', [
            'shifts' => new CashRegisterShiftCollection($shifts),
            'filters' => $filters,
        ]);
    }

    public function openShift(OpenShiftRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var CashRegister $register */
        $register = CashRegister::query()->findOrFail($validated['cash_register_id']);

        $this->shiftService->openShift(
            register: $register,
            cashier: $request->user() ?? abort(401),
            openingBalance: (float) $validated['opening_balance'],
            notes: $validated['notes'] ?? null,
        );

        return redirect()->route('cash-registers.shifts.index');
    }

    public function closeShift(CloseShiftRequest $request, CashRegisterShift $shift): RedirectResponse
    {
        $validated = $request->validated();

        $this->shiftService->closeShift(
            shift: $shift,
            closingBalance: (float) $validated['closing_balance'],
            notes: $validated['notes'] ?? null,
        );

        return redirect()->route('cash-registers.shifts.index');
    }

    public function forceCloseShift(ForceCloseShiftRequest $request, CashRegisterShift $shift): RedirectResponse
    {
        $validated = $request->validated();

        $this->shiftService->forceCloseShift(
            shift: $shift,
            manager: $request->user() ?? abort(401),
            closingBalance: (float) $validated['closing_balance'],
            notes: $validated['notes'] ?? null,
        );

        return redirect()->route('cash-registers.shifts.index');
    }

    public function show(CashRegisterShift $shift): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SHIFTS_VIEW);

        $shift->load(['register', 'cashier', 'movements.user']);

        return Inertia::render('CashRegisterShifts/Show/Index', [
            'shift' => new CashRegisterShiftResource($shift),
        ]);
    }
}
