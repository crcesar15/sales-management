<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\Role\RoleCollection;
use App\Http\Resources\Role\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

final class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::ROLES_VIEW->value, auth()->user());

        $roles = $this->roleService->list(
            orderBy: request()->string('order_by', 'name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 10),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('Roles/Index', [
            'roles' => new RoleCollection($roles),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'order_by' => request()->string('order_by', 'name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 10),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::ROLES_CREATE->value, auth()->user());

        return Inertia::render('Roles/Create/Index', [
            'availablePermissions' => $this->roleService->getAvailablePermissions(),
            'availableUsers' => $this->roleService->getAvailableUsers(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleService->create($request->validated());

        return redirect()->route('roles');
    }

    public function edit(Role $role): InertiaResponse
    {
        $this->authorize(PermissionsEnum::ROLES_EDIT->value, auth()->user());

        return Inertia::render('Roles/Edit/Index', [
            'role' => (new RoleResource($role))->resolve(),
            'permissions' => $role->permissions()->pluck('name')->toArray(),
            'availablePermissions' => $this->roleService->getAvailablePermissions(),
            'availableUsers' => $this->roleService->getAvailableUsers(),
            'assignedUsers' => $role->users()->select('users.id', 'users.first_name', 'users.last_name', 'users.email')->get()->map(fn ($u) => [
                'id' => $u->id,
                'full_name' => "{$u->first_name} {$u->last_name}",
                'email' => $u->email,
            ]),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        return redirect()->route('roles');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize(PermissionsEnum::ROLES_DELETE->value, auth()->user());

        $this->roleService->delete($role);

        return redirect()->route('roles');
    }
}
