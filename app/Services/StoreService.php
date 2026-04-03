<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class StoreService
{
    /**
     * Paginated, filtered and sorted list of stores.
     *
     * @return LengthAwarePaginator<int, Store>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'name',
        string $orderDirection = 'asc',
        int $perPage = 20,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return Store::query()
            ->withCount('users')
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%{$filter}%")
                    ->orWhere('code', 'like', "%{$filter}%")
                )
            )
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->when($status !== 'all' && $status !== 'archived', fn ($q) => $q->where('status', $status))
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new store within a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Store
    {
        return DB::transaction(function () use ($data): Store {
            $data['code'] = mb_strtoupper($data['code']);

            $store = Store::create($data);

            activity('store')
                ->performedOn($store)
                ->causedBy(auth()->user())
                ->withProperties(['status' => $store->status])
                ->log('created');

            return $store;
        });
    }

    /**
     * Update a store within a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Store $store, array $data): Store
    {
        return DB::transaction(function () use ($store, $data): Store {
            if (isset($data['code'])) {
                $data['code'] = mb_strtoupper($data['code']);
            }

            $store->update($data);

            activity('store')
                ->performedOn($store)
                ->causedBy(auth()->user())
                ->withProperties($store->getChanges())
                ->log('updated');

            return $store;
        });
    }

    /**
     * Soft-delete a store.
     */
    public function delete(Store $store): void
    {
        DB::transaction(function () use ($store): void {
            $store->delete();

            activity('store')
                ->performedOn($store)
                ->causedBy(auth()->user())
                ->log('deleted');
        });
    }

    /**
     * Restore a soft-deleted store.
     */
    public function restore(Store $store): void
    {
        DB::transaction(function () use ($store): void {
            $store->restore();
            $store->update(['status' => 'active']);

            activity('store')
                ->performedOn($store)
                ->causedBy(auth()->user())
                ->withProperties(['status' => 'active'])
                ->log('restored');
        });
    }

    /**
     * Update only the store's status.
     */
    public function updateStatus(Store $store, string $status): Store
    {
        $oldStatus = $store->status;

        $store->update(['status' => $status]);

        activity('store')
            ->performedOn($store)
            ->causedBy(auth()->user())
            ->withProperties(['old_status' => $oldStatus, 'status' => $status])
            ->log('status_changed');

        return $store;
    }

    /**
     * Assign a user to a store.
     */
    public function assignUser(Store $store, User $user): Store
    {
        if ($store->users()->where('users.id', $user->id)->exists()) {
            return $store;
        }

        $store->users()->attach($user->id);

        activity('store')
            ->performedOn($store)
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => $user->id, 'user_name' => $user->full_name])
            ->log('user_assigned');

        return $store->load('users');
    }

    /**
     * Remove a user from a store.
     */
    public function removeUser(Store $store, User $user): void
    {
        $store->users()->detach($user->id);

        activity('store')
            ->performedOn($store)
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => $user->id, 'user_name' => $user->full_name])
            ->log('user_removed');
    }
}
