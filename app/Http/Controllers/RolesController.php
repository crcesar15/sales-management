<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function index()
    {
        return Inertia::render('roles/index');
    }

    public function create()
    {
        return Inertia::render('roles/create/index');
    }

    public function edit(Role $role)
    {
        $permissions = $role->permissions->pluck('name')->toArray();

        return Inertia::render('roles/edit/index', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }
}
