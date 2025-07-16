<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Spatie\Permission\Models\Role;

final class RolesController extends Controller
{
    public function index()
    {
        $this->authorize('roles-view', auth()->user());

        return Inertia::render('roles/index');
    }

    public function create()
    {
        $this->authorize('roles-create', auth()->user());

        return Inertia::render('roles/create/index');
    }

    public function edit(Role $role)
    {
        $this->authorize('roles-edit', auth()->user());

        $permissions = $role->permissions->pluck('name')->toArray();

        return Inertia::render('roles/edit/index', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }
}
