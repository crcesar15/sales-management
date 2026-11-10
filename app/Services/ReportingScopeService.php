<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ReportingScopeService
{
    /** @return Collection<int, Store> */
    public function stores(User $user): Collection
    {
        return Store::withTrashed()
            ->when(! $user->hasRole(RolesEnum::ADMIN->value), fn ($query) => $query
                ->whereHas('users', fn ($users) => $users->where('users.id', $user->id)))
            ->orderBy('name')->get(['id', 'name', 'code', 'status', 'deleted_at']);
    }

    /** @return list<int> */
    public function storeIds(User $user, ?int $storeId = null): array
    {
        $ids = array_values($this->stores($user)->map(fn (Store $store): int => $store->id)->all());
        if ($storeId !== null) {
            abort_unless(in_array($storeId, $ids, true), 403);

            return [$storeId];
        }

        return $ids;
    }

    /** @return Builder<SalesOrder> */
    public function sales(User $user, ?int $storeId = null): Builder
    {
        return SalesOrder::query()->whereIn('store_id', $this->storeIds($user, $storeId))
            ->when(! $user->can(PermissionsEnum::SALES_VIEW_ALL->value), fn ($query) => $query->where('user_id', $user->id));
    }

    public function assertSale(User $user, SalesOrder $order): void
    {
        abort_unless($this->sales($user)->whereKey($order->id)->exists(), 403);
    }

    public function canViewCost(User $user, int $storeId): bool
    {
        if (! $user->can(PermissionsEnum::DASHBOARD_PROFIT_VIEW->value)) {
            return false;
        }
        if ($user->hasRole(RolesEnum::ADMIN->value)) {
            return true;
        }
        $user->loadMissing(['stores' => fn ($query) => $query->withTrashed()]);

        return $user->stores->contains('id', $storeId);
    }
}
