<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

final class UsersController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $this->authorize('users-view', auth()->user());

        return Inertia::render('users/index');
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorize('users-create', auth()->user());

        // List of Roles
        $roles = Role::all();

        return Inertia::render('users/create/index', [
            'availableRoles' => $roles,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->authorize('users-edit', auth()->user());

        // Include the user role with only id and name without pivot
        $user->load(['roles' => function ($query): void {
            $query->select('id', 'name');
        }]);

        // remove password and remember_token from the user object
        $user->makeHidden(['password', 'remember_token']);

        // List of Roles
        $roles = Role::all();

        return Inertia::render('users/edit/index', [
            'user' => $user,
            'availableRoles' => $roles,
        ]);
    }
}
