<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class UserService
{
    /**
     * Paginated, filtered and sorted list of users.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'first_name',
        string $orderDirection = 'asc',
        int $perPage = 10,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return User::query()
            ->with(['roles'])
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$filter}%")
                    ->orWhere('last_name', 'like', "%{$filter}%")
                    ->orWhere('email', 'like', "%{$filter}%")
                )
            )
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->when($status !== 'all' && $status !== 'archived', fn ($q) => $q->where('status', $status))
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new user, assign role and stores within a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'additional_properties' => $data['additional_properties'] ?? null,
            ]);

            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            } elseif (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            if (! empty($data['store_ids'])) {
                $user->stores()->attach($data['store_ids']);
            }

            activity('users')
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['status' => $user->status])
                ->log('created');

            return $user->load(['roles', 'stores']);
        });
    }

    /**
     * Update a user's profile, roles and stores.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->update([
                'first_name' => $data['first_name'] ?? $user->first_name,
                'last_name' => $data['last_name'] ?? $user->last_name,
                'email' => $data['email'] ?? $user->email,
                'username' => $data['username'] ?? $user->username,
                'phone' => $data['phone'] ?? $user->phone,
                'status' => $data['status'] ?? $user->status,
                'date_of_birth' => $data['date_of_birth'] ?? $user->date_of_birth,
                'additional_properties' => $data['additional_properties'] ?? $user->additional_properties,
            ]);

            if (isset($data['password'])) {
                $user->update(['password' => $data['password']]);
            }

            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            } elseif (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles']);
            }

            if (array_key_exists('store_ids', $data)) {
                $user->stores()->sync($data['store_ids'] ?? []);
            }

            activity('users')
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties($user->getChanges())
                ->log('updated');

            return $user->load(['roles', 'stores']);
        });
    }

    /**
     * Update only the user's status.
     */
    public function updateStatus(User $user, string $status): User
    {
        $oldStatus = $user->status;

        $user->update(['status' => $status]);

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['old_status' => $oldStatus, 'status' => $status])
            ->log('status_changed');

        return $user;
    }

    /**
     * Assign a store to a user.
     */
    public function assignStore(User $user, Store $store): User
    {
        if (! $user->stores()->where('stores.id', $store->id)->exists()) {
            $user->stores()->attach($store->id);
        }

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['store_id' => $store->id, 'store_name' => $store->name])
            ->log('store_assigned');

        return $user->load(['roles', 'stores']);
    }

    /**
     * Remove a store assignment from a user.
     */
    public function removeStore(User $user, Store $store): void
    {
        $user->stores()->detach($store->id);

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['store_id' => $store->id, 'store_name' => $store->name])
            ->log('store_removed');
    }

    /**
     * Soft-delete a user and set status to archived.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->update(['status' => 'archived']);
            $user->delete();

            activity('users')
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['status' => 'archived'])
                ->log('deleted');
        });
    }
}
