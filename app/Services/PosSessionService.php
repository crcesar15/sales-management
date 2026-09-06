<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashRegisterShiftStatus;
use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class PosSessionService
{
    public function __construct(private readonly CashRegisterShiftService $shiftService) {}

    public function currentShift(User $user): ?CashRegisterShift
    {
        return CashRegisterShift::query()
            ->with(['register.store', 'cashier'])
            ->where('user_id', $user->id)
            ->where('status', CashRegisterShiftStatus::OPEN)
            ->latest('opened_at')
            ->first();
    }

    /** @return Collection<int, CashRegister> */
    public function availableRegisters(User $user): Collection
    {
        return CashRegister::query()
            ->with('store')
            ->where('status', CashRegisterStatus::ACTIVE)
            ->whereHas('store.users', fn ($query) => $query->whereKey($user->id))
            ->whereDoesntHave('currentShift')
            ->orderBy('name')
            ->get();
    }

    public function openShift(User $user, int $registerId, float $openingBalance): CashRegisterShift
    {
        $register = CashRegister::query()
            ->whereKey($registerId)
            ->whereHas('store.users', fn ($query) => $query->whereKey($user->id))
            ->firstOrFail();

        return $this->shiftService->openShift($register, $user, $openingBalance);
    }
}
