<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashRegisterShiftStatus;
use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

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

    public function openShift(User $user, int $registerId, float $openingBalance, ?string $openingNotes = null): CashRegisterShift
    {
        $register = CashRegister::query()
            ->whereKey($registerId)
            ->whereHas('store.users', fn ($query) => $query->whereKey($user->id))
            ->firstOrFail();

        return $this->shiftService->openShift($register, $user, $openingBalance, $openingNotes);
    }

    /** @return array{opening_balance: float, cash_sales: float, cash_sales_count: int, cash_in: float, cash_out: float, expected_closing: float} */
    public function closingSummary(User $user): array
    {
        return $this->shiftService->reconciliation($this->requiredCurrentShift($user));
    }

    public function closeShift(
        User $user,
        float $closingBalance,
        ?string $closingNotes = null,
        ?string $discrepancyReason = null,
    ): CashRegisterShift {
        return $this->shiftService->closeShift(
            $this->requiredCurrentShift($user),
            $closingBalance,
            $closingNotes,
            $discrepancyReason,
        );
    }

    public function addMovement(User $user, string $type, float $amount, string $reason): CashRegisterShift
    {
        $shift = $this->requiredCurrentShift($user);
        $this->shiftService->addMovement($shift, $type, $amount, $reason, $user);

        return $shift->fresh(['register.store', 'cashier']) ?? $shift;
    }

    /** @return array{opening_balance: float, cash_sales: float, cash_sales_count: int, cash_in: float, cash_out: float, expected_closing: float} */
    public function reconciliation(CashRegisterShift $shift): array
    {
        return $this->shiftService->reconciliation($shift);
    }

    private function requiredCurrentShift(User $user): CashRegisterShift
    {
        return $this->currentShift($user) ?? throw new InvalidArgumentException('No open shift was found for this cashier.');
    }
}
