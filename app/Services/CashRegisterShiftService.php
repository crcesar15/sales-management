<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashMovementType;
use App\Enums\CashRegisterShiftStatus;
use App\Enums\CashRegisterStatus;
use App\Enums\PaymentMethod;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\CashRegister;
use App\Models\CashRegisterMovement;
use App\Models\CashRegisterShift;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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
                fn ($q) => $q->where('opened_at', '>=', Carbon::parse($filters['date_from'], config('app.timezone'))->startOfDay()),
            )
            ->when(
                isset($filters['date_to']),
                fn ($q) => $q->where('opened_at', '<=', Carbon::parse($filters['date_to'], config('app.timezone'))->endOfDay()),
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
        ?string $openingNotes = null,
    ): CashRegisterShift {
        return DB::transaction(function () use ($register, $cashier, $openingBalance, $openingNotes): CashRegisterShift {
            $lockedRegister = CashRegister::query()
                ->lockForUpdate()
                ->findOrFail($register->id);

            if ($lockedRegister->status !== CashRegisterStatus::ACTIVE) {
                throw new InvalidArgumentException('Cannot open a shift on an inactive register.');
            }

            if (CashRegisterShift::query()
                ->where('cash_register_id', $lockedRegister->id)
                ->where('status', CashRegisterShiftStatus::OPEN)
                ->exists()) {
                throw new InvalidArgumentException('A shift is already open on this register.');
            }

            if (CashRegisterShift::query()
                ->where('user_id', $cashier->id)
                ->where('status', CashRegisterShiftStatus::OPEN)
                ->exists()) {
                throw new InvalidArgumentException('This cashier already has an open shift.');
            }

            $shift = CashRegisterShift::create([
                'cash_register_id' => $lockedRegister->id,
                'user_id' => $cashier->id,
                'status' => CashRegisterShiftStatus::OPEN,
                'opening_balance' => $openingBalance,
                'opened_at' => now(),
                'opening_notes' => $openingNotes,
            ]);

            activity('cash_register_shift')
                ->performedOn($shift)
                ->causedBy(auth()->user())
                ->withProperties(['register' => $lockedRegister->name])
                ->log("Shift opened on register {$lockedRegister->name}");

            return $shift->load(['register', 'cashier']);
        });
    }

    /**
     * Close a shift normally.
     *
     * @throws InvalidArgumentException if shift is not open
     */
    public function closeShift(
        CashRegisterShift $shift,
        float $closingBalance,
        ?string $closingNotes = null,
        ?string $discrepancyReason = null,
    ): CashRegisterShift {
        return DB::transaction(function () use ($shift, $closingBalance, $closingNotes, $discrepancyReason): CashRegisterShift {
            $lockedShift = CashRegisterShift::query()->lockForUpdate()->findOrFail($shift->id);
            $this->validateTransition($lockedShift->status->value, CashRegisterShiftStatus::CLOSED->value);
            $this->requireNoOpenPosDrafts($lockedShift);
            $reconciliation = $this->reconciliation($lockedShift);
            $difference = round($closingBalance - $reconciliation['expected_closing'], 2);
            $this->validateDiscrepancyReason($difference, $discrepancyReason);

            $lockedShift->update([
                'status' => CashRegisterShiftStatus::CLOSED,
                'closing_balance' => $closingBalance,
                'expected_closing' => $reconciliation['expected_closing'],
                'difference' => $difference,
                'closed_at' => now(),
                'closing_notes' => $closingNotes,
                'discrepancy_reason' => $discrepancyReason,
            ]);

            activity('cash_register_shift')
                ->performedOn($lockedShift)
                ->causedBy(auth()->user())
                ->withProperties(['difference' => $difference])
                ->log("Shift closed. Difference: {$difference}");

            return $lockedShift->load(['register', 'cashier', 'movements']);
        });
    }

    /**
     * Force-close a shift with manager permission.
     *
     * @throws InvalidArgumentException if shift is not open
     */
    public function forceCloseShift(
        CashRegisterShift $shift,
        User $manager,
        float $closingBalance,
        ?string $closingNotes = null,
        ?string $discrepancyReason = null,
    ): CashRegisterShift {
        return DB::transaction(function () use ($shift, $manager, $closingBalance, $closingNotes, $discrepancyReason): CashRegisterShift {
            $lockedShift = CashRegisterShift::query()->lockForUpdate()->findOrFail($shift->id);
            $this->validateTransition($lockedShift->status->value, CashRegisterShiftStatus::FORCED_CLOSE->value);
            $this->requireNoOpenPosDrafts($lockedShift);
            $reconciliation = $this->reconciliation($lockedShift);
            $difference = round($closingBalance - $reconciliation['expected_closing'], 2);
            $this->validateDiscrepancyReason($difference, $discrepancyReason);

            $lockedShift->update([
                'status' => CashRegisterShiftStatus::FORCED_CLOSE,
                'closing_balance' => $closingBalance,
                'expected_closing' => $reconciliation['expected_closing'],
                'difference' => $difference,
                'closed_at' => now(),
                'closing_notes' => $closingNotes,
                'discrepancy_reason' => $discrepancyReason,
            ]);

            activity('cash_register_shift')
                ->performedOn($lockedShift)
                ->causedBy(auth()->user())
                ->withProperties(['manager' => $manager->full_name, 'difference' => $difference])
                ->log("Shift force-closed by {$manager->full_name}");

            return $lockedShift->load(['register', 'cashier', 'movements']);
        });
    }

    /**
     * Add a cash movement to an open shift.
     *
     * @throws InvalidArgumentException if shift is not open
     */
    public function addMovement(CashRegisterShift $shift, string $type, float $amount, string $reason, User $user): CashRegisterMovement
    {
        return DB::transaction(function () use ($shift, $type, $amount, $reason, $user): CashRegisterMovement {
            $lockedShift = CashRegisterShift::query()->lockForUpdate()->findOrFail($shift->id);

            if ($lockedShift->status !== CashRegisterShiftStatus::OPEN) {
                throw new InvalidArgumentException("Cannot add a movement to a {$lockedShift->status->value} shift.");
            }

            $movement = CashRegisterMovement::create([
                'cash_register_shift_id' => $lockedShift->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            activity('cash_register_movement')
                ->performedOn($movement)
                ->causedBy(auth()->user())
                ->withProperties(['type' => $type, 'amount' => $amount, 'shift_id' => $lockedShift->id])
                ->log("Movement ({$type}) of {$amount} added to shift {$lockedShift->id}");

            return $movement->load('user');
        });
    }

    /**
     * @return array{opening_balance: float, cash_sales: float, cash_sales_count: int, cash_in: float, cash_out: float, expected_closing: float}
     */
    public function reconciliation(CashRegisterShift $shift): array
    {
        $cashIn = (float) $shift->movements()
            ->where('type', CashMovementType::CASH_IN->value)
            ->sum('amount');

        $cashOut = (float) $shift->movements()
            ->where('type', CashMovementType::CASH_OUT->value)
            ->sum('amount');

        $cashSalesQuery = $shift->salesOrderPayments()
            ->where('payment_method', PaymentMethod::CASH->value);
        $cashSales = (float) $cashSalesQuery->sum('amount');
        $openingBalance = (float) $shift->opening_balance;

        return [
            'opening_balance' => $openingBalance,
            'cash_sales' => $cashSales,
            'cash_sales_count' => $cashSalesQuery->count(),
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'expected_closing' => round($openingBalance + $cashIn - $cashOut + $cashSales, 2),
        ];
    }

    private function requireNoOpenPosDrafts(CashRegisterShift $shift): void
    {
        if (SalesOrder::query()
            ->where('cash_register_shift_id', $shift->id)
            ->where('status', SalesOrderStatus::DRAFT)
            ->where('payment_status', SalesOrderPaymentStatus::PENDING)
            ->exists()) {
            throw new InvalidArgumentException('Complete or discard the active POS sale before closing this shift.');
        }
    }

    private function validateTransition(string $from, string $to): void
    {
        $allowed = self::TRANSITION_MAP[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException("Cannot transition shift from {$from} to {$to}.");
        }
    }

    private function validateDiscrepancyReason(float $difference, ?string $discrepancyReason): void
    {
        if ($difference !== 0.0 && blank($discrepancyReason)) {
            throw new InvalidArgumentException('A discrepancy reason is required when the counted cash does not match the expected closing.');
        }
    }
}
