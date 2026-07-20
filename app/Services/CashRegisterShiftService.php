<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashMovementType;
use App\Enums\CashRegisterShiftStatus;
use App\Enums\CashRegisterStatus;
use App\Enums\PaymentMethod;
use App\Models\CashRegister;
use App\Models\CashRegisterMovement;
use App\Models\CashRegisterShift;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CashRegisterShiftService
{
    private const TRANSITION_MAP = [
        'open' => ['closed', 'forced_close'],
        'closed' => [],
        'forced_close' => [],
    ];

    /**
     * Paginated, filtered list of shifts.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, CashRegisterShift>
     */
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return CashRegisterShift::query()
            ->with(['register', 'cashier', 'movements'])
            ->when(
                isset($filters['cash_register_id']),
                fn ($q) => $q->where('cash_register_id', $filters['cash_register_id']),
            )
            ->when(
                isset($filters['user_id']),
                fn ($q) => $q->where('user_id', $filters['user_id']),
            )
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status']),
            )
            ->when(
                isset($filters['date_from']),
                fn ($q) => $q->where('opened_at', '>=', $filters['date_from']),
            )
            ->when(
                isset($filters['date_to']),
                fn ($q) => $q->where('opened_at', '<=', $filters['date_to']),
            )
            ->orderBy('opened_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Open a new shift on a cash register.
     *
     * @throws InvalidArgumentException if register is inactive or already has an open shift
     */
    public function openShift(
        CashRegister $register,
        User $cashier,
        float $openingBalance,
        ?string $notes = null,
    ): CashRegisterShift {
        if ($register->status !== CashRegisterStatus::ACTIVE) {
            throw new InvalidArgumentException('Cannot open a shift on an inactive register.');
        }

        if (CashRegisterShift::where('cash_register_id', $register->id)->where('status', CashRegisterShiftStatus::OPEN)->exists()) {
            throw new InvalidArgumentException('A shift is already open on this register.');
        }

        return DB::transaction(function () use ($register, $cashier, $openingBalance, $notes): CashRegisterShift {
            $shift = CashRegisterShift::create([
                'cash_register_id' => $register->id,
                'user_id' => $cashier->id,
                'status' => CashRegisterShiftStatus::OPEN,
                'opening_balance' => $openingBalance,
                'opened_at' => now(),
                'notes' => $notes,
            ]);

            activity('cash_register_shift')
                ->performedOn($shift)
                ->causedBy(auth()->user())
                ->withProperties(['register' => $register->name])
                ->log("Shift opened on register {$register->name}");

            return $shift->load(['register', 'cashier']);
        });
    }

    /**
     * Close a shift normally.
     *
     * @throws InvalidArgumentException if shift is not open
     */
    public function closeShift(CashRegisterShift $shift, float $closingBalance, ?string $notes = null): CashRegisterShift
    {
        $this->validateTransition($shift->status->value, CashRegisterShiftStatus::CLOSED->value);

        return DB::transaction(function () use ($shift, $closingBalance, $notes): CashRegisterShift {
            $expectedClosing = $this->calculateExpectedClosing($shift);
            $difference = round($closingBalance - $expectedClosing, 2);

            $shift->update([
                'status' => CashRegisterShiftStatus::CLOSED,
                'closing_balance' => $closingBalance,
                'expected_closing' => $expectedClosing,
                'difference' => $difference,
                'closed_at' => now(),
                'notes' => $notes ?? $shift->notes,
            ]);

            activity('cash_register_shift')
                ->performedOn($shift)
                ->causedBy(auth()->user())
                ->withProperties(['difference' => $difference])
                ->log("Shift closed. Difference: {$difference}");

            return $shift->load(['register', 'cashier', 'movements']);
        });
    }

    /**
     * Force-close a shift with manager permission.
     *
     * @throws InvalidArgumentException if shift is not open
     */
    public function forceCloseShift(CashRegisterShift $shift, User $manager, float $closingBalance, ?string $notes = null): CashRegisterShift
    {
        $this->validateTransition($shift->status->value, CashRegisterShiftStatus::FORCED_CLOSE->value);

        return DB::transaction(function () use ($shift, $manager, $closingBalance, $notes): CashRegisterShift {
            $expectedClosing = $this->calculateExpectedClosing($shift);
            $difference = round($closingBalance - $expectedClosing, 2);

            $shift->update([
                'status' => CashRegisterShiftStatus::FORCED_CLOSE,
                'closing_balance' => $closingBalance,
                'expected_closing' => $expectedClosing,
                'difference' => $difference,
                'closed_at' => now(),
                'notes' => $notes ?? $shift->notes,
            ]);

            activity('cash_register_shift')
                ->performedOn($shift)
                ->causedBy(auth()->user())
                ->withProperties(['manager' => $manager->full_name, 'difference' => $difference])
                ->log("Shift force-closed by {$manager->full_name}");

            return $shift->load(['register', 'cashier', 'movements']);
        });
    }

    /**
     * Add a cash movement to an open shift.
     *
     * @throws InvalidArgumentException if shift is not open
     */
    public function addMovement(CashRegisterShift $shift, string $type, float $amount, string $reason, User $user): CashRegisterMovement
    {
        $this->validateTransition($shift->status->value, CashRegisterShiftStatus::OPEN->value);

        return DB::transaction(function () use ($shift, $type, $amount, $reason, $user): CashRegisterMovement {
            $movement = CashRegisterMovement::create([
                'cash_register_shift_id' => $shift->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            activity('cash_register_movement')
                ->performedOn($movement)
                ->causedBy(auth()->user())
                ->withProperties(['type' => $type, 'amount' => $amount, 'shift_id' => $shift->id])
                ->log("Movement ({$type}) of {$amount} added to shift {$shift->id}");

            return $movement->load('user');
        });
    }

    private function validateTransition(string $from, string $to): void
    {
        $allowed = self::TRANSITION_MAP[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException("Cannot transition shift from {$from} to {$to}.");
        }
    }

    /**
     * Calculate the expected closing balance for a shift.
     * opening_balance + cash_in movements - cash_out movements + cash sales.
     */
    private function calculateExpectedClosing(CashRegisterShift $shift): float
    {
        $cashIn = (float) $shift->movements()
            ->where('type', CashMovementType::CASH_IN->value)
            ->sum('amount');

        $cashOut = (float) $shift->movements()
            ->where('type', CashMovementType::CASH_OUT->value)
            ->sum('amount');

        $cashSales = (float) $shift->salesOrderPayments()
            ->where('payment_method', PaymentMethod::CASH->value)
            ->sum('amount');

        return round((float) $shift->opening_balance + $cashIn - $cashOut + $cashSales, 2);
    }
}
