<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\CashRegisters\StoreCashRegisterRequest;
use App\Http\Requests\CashRegisters\UpdateCashRegisterRequest;
use App\Http\Resources\CashRegister\CashRegisterCollection;
use App\Http\Resources\CashRegister\CashRegisterResource;
use App\Models\CashRegister;
use App\Services\CashRegisterService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

final class CashRegisterController extends Controller
{
    private readonly CashRegisterService $cashRegisterService;

    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CASH_REGISTERS_VIEW);

        $storeId = request()->integer('store_id') ?: null;
        $status = request()->string('status', 'all')->value();

        $registers = $this->cashRegisterService->list(
            storeId: $storeId,
            status: $status,
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('CashRegisters/Index', [
            'registers' => new CashRegisterCollection($registers),
            'filters' => [
                'store_id' => $storeId,
                'status' => $status,
                'filter' => request()->string('filter')->value() ?: null,
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CASH_REGISTERS_CREATE);

        return Inertia::render('CashRegisters/Create/Index');
    }

    public function store(StoreCashRegisterRequest $request): RedirectResponse
    {
        $this->cashRegisterService->create($request->validated());

        return redirect()->route('cash-registers');
    }

    public function edit(CashRegister $cashRegister): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CASH_REGISTERS_EDIT);

        $cashRegister->load(['store', 'currentShift']);

        return Inertia::render('CashRegisters/Edit/Index', [
            'cashRegister' => (new CashRegisterResource($cashRegister))->resolve(),
        ]);
    }

    public function update(UpdateCashRegisterRequest $request, CashRegister $cashRegister): RedirectResponse
    {
        $this->cashRegisterService->update($cashRegister, $request->validated());

        return redirect()->route('cash-registers');
    }

    public function destroy(CashRegister $cashRegister): RedirectResponse
    {
        $this->authorize(PermissionsEnum::CASH_REGISTERS_DELETE);

        try {
            $this->cashRegisterService->delete($cashRegister);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['register' => $e->getMessage()]);
        }

        return redirect()->route('cash-registers');
    }
}
