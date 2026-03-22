<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleService
{
    /**
     * Paginated, filtered and sorted list of roles.
     *
     * @return LengthAwarePaginator<int, Role>
     */
    public function list(
        string $orderBy = 'name',
        string $orderDirection = 'asc',
        int $perPage = 10,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return Role::query()
            ->orderBy($orderBy, $orderDirection)
            ->when($filter !== null && $filter !== '', fn ($q) => $q->where('name', 'like', '%' . $filter . '%'))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get all permissions ordered by category and name.
     *
     * @return Collection<int, Permission>
     */
    public function getAvailablePermissions(): Collection
    {
        return Permission::query()
            ->select('id', 'name', 'category')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new role and sync its permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = new Role([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->save();

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    /**
     * Update a role's name and permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->update(['name' => $data['name']]);

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    /**
     * Detach all users from the role and delete it.
     */
    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $role->users()->detach();
            $role->delete();
        });
    }
}
