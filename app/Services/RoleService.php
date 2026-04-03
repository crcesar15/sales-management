<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
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
     * Get all users available for role assignment.
     *
     * @return Collection<int, User>
     */
    public function getAvailableUsers(): Collection
    {
        return User::select('id', 'first_name', 'last_name', 'email')
            ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name")
            ->orderBy('first_name')
            ->get();
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
     * Create a new role and sync its permissions and users.
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

            if (isset($data['users'])) {
                $role->users()->sync($data['users']);
            }

            return $role;
        });
    }

    /**
     * Update a role's name, permissions and users.
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

            if (isset($data['users'])) {
                $role->users()->sync($data['users']);
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
