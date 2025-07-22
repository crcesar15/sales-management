<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

final class RolesController extends Controller
{
    public function index(): InertiaResponse
    {
        $this->authorize('roles-view', auth()->user());

        return Inertia::render('roles/index');
    }

    public function create(): InertiaResponse
    {
        $this->authorize('roles-create', auth()->user());

        return Inertia::render('roles/create/index');
    }

    public function edit(Role $role): InertiaResponse
    {
        $this->authorize('roles-edit', auth()->user());

        $permissions = $role->permissions()->get()->pluck('name')->toArray();

        return Inertia::render('roles/edit/index', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }
}
