<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashRegister;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CashRegisterService
{
    /**
     * Paginated, filtered list of cash registers for a store.
     *
     * @return LengthAwarePaginator<int, CashRegister>
     */
    public function list(
        int $storeId,
        string $status = 'all',
        int $perPage = 20,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return CashRegister::query()
            ->with('currentShift')
            ->where('store_id', $storeId)
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$filter}%")
                    ->orWhere('code', 'like', "%{$filter}%")
                ),
            )
            ->when(
                $status !== 'all',
                fn ($q) => $q->where('status', $status),
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new cash register within a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CashRegister
    {
        return DB::transaction(function () use ($data): CashRegister {
            if (! empty($data['is_default'])) {
                CashRegister::where('store_id', $data['store_id'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $register = CashRegister::create($data);

            activity('cash_register')
                ->performedOn($register)
                ->causedBy(auth()->user())
                ->log('created');

            return $register;
        });
    }

    /**
     * Update a cash register within a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CashRegister $register, array $data): CashRegister
    {
        return DB::transaction(function () use ($register, $data): CashRegister {
            if (! empty($data['is_default'])) {
                CashRegister::where('store_id', $register->store_id)
                    ->where('is_default', true)
                    ->where('id', '!=', $register->id)
                    ->update(['is_default' => false]);
            }

            $register->update($data);

            activity('cash_register')
                ->performedOn($register)
                ->causedBy(auth()->user())
                ->withProperties($register->getChanges())
                ->log('updated');

            return $register;
        });
    }

    /**
     * Delete a cash register. Blocked if the register has any shifts.
     *
     * @throws InvalidArgumentException
     */
    public function delete(CashRegister $register): void
    {
        if ($register->shifts()->exists()) {
            throw new InvalidArgumentException('Cannot delete a cash register that has shifts.');
        }

        DB::transaction(function () use ($register): void {
            $register->delete();

            activity('cash_register')
                ->performedOn($register)
                ->causedBy(auth()->user())
                ->log('deleted');
        });
    }
}
