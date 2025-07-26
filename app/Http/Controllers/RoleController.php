<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

final class RoleController extends Controller
{
    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::ROLES_VIEW->value, auth()->user());

        return Inertia::render('roles/index');
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::ROLES_CREATE->value, auth()->user());

        return Inertia::render('roles/create/index');
    }

    public function edit(Role $role): InertiaResponse
    {
        $this->authorize(PermissionsEnum::ROLES_EDIT->value, auth()->user());

        $permissions = $role->permissions()->get()->pluck('name')->toArray();

        return Inertia::render('roles/edit/index', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }
}
