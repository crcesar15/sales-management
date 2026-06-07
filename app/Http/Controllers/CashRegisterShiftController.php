<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\CashRegisterMovements\StoreMovementRequest;
use App\Http\Requests\CashRegisterShifts\CloseShiftRequest;
use App\Http\Requests\CashRegisterShifts\ForceCloseShiftRequest;
use App\Http\Requests\CashRegisterShifts\OpenShiftRequest;
use App\Http\Resources\CashRegisterShift\CashRegisterShiftCollection;
use App\Http\Resources\CashRegisterShift\CashRegisterShiftResource;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\User;
use App\Services\CashRegisterShiftService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

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
            'status' => request()->string('status')->value() ?: null,
            'date_from' => request()->string('date_from')->value() ?: null,
            'date_to' => request()->string('date_to')->value() ?: null,
        ];

        $shifts = $this->shiftService->list(
            filters: $filters,
            perPage: request()->integer('per_page', 20),
        );

        return Inertia::render('CashRegisterShifts/Index', [
            'shifts' => new CashRegisterShiftCollection($shifts),
            'registers' => CashRegister::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']),
            'cashiers' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name'])->map(fn (User $u) => ['id' => $u->id, 'full_name' => $u->first_name . ' ' . $u->last_name]),
            'filters' => array_merge($filters, ['per_page' => request()->integer('per_page', 20)]),
        ]);
    }

    public function openShift(OpenShiftRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var CashRegister $register */
        $register = CashRegister::query()->findOrFail($validated['cash_register_id']);

        try {
            $this->shiftService->openShift(
                register: $register,
                cashier: $request->user() ?? abort(401),
                openingBalance: (float) $validated['opening_balance'],
                notes: $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['shift' => $e->getMessage()]);
        }

        return redirect()->route('shifts');
    }

    public function closeShift(CloseShiftRequest $request, CashRegisterShift $shift): RedirectResponse
    {
        // If not the shift opener, require shift.manage permission
        if ($shift->user_id !== $request->user()?->id) {
            $this->authorize(PermissionsEnum::SHIFTS_MANAGE);
        } else {
            $this->authorize(PermissionsEnum::SHIFTS_CLOSE);
        }

        $validated = $request->validated();

        try {
            $this->shiftService->closeShift(
                shift: $shift,
                closingBalance: (float) $validated['closing_balance'],
                notes: $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['shift' => $e->getMessage()]);
        }

        return redirect()->route('shifts');
    }

    public function forceCloseShift(ForceCloseShiftRequest $request, CashRegisterShift $shift): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->shiftService->forceCloseShift(
                shift: $shift,
                manager: $request->user() ?? abort(401),
                closingBalance: (float) $validated['closing_balance'],
                notes: $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['shift' => $e->getMessage()]);
        }

        return redirect()->route('shifts');
    }

    public function show(CashRegisterShift $shift): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SHIFTS_VIEW);

        $shift->load(['register.store', 'cashier', 'movements.user']);

        return Inertia::render('CashRegisterShifts/Show/Index', [
            'shift' => (new CashRegisterShiftResource($shift))->resolve(),
        ]);
    }

    public function addMovement(StoreMovementRequest $request, CashRegisterShift $shift): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->shiftService->addMovement(
                shift: $shift,
                type: $validated['type'],
                amount: (float) $validated['amount'],
                reason: $validated['reason'],
                user: $request->user() ?? abort(401),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['movement' => $e->getMessage()]);
        }

        return redirect()->route('shifts.show', $shift->id)->with('success', 'Movement added successfully.');
    }
}
