<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        return Inertia::render('users/index');
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
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
        // Include the user role with only id and name without pivot
        $user->load(['roles' => function ($query) {
            $query->select('id', 'name');
        }]);

        //remove password and remember_token from the user object
        $user->makeHidden(['password', 'remember_token']);

        // List of Roles
        $roles = Role::all();

        return Inertia::render('users/edit/index', [
            'user' => $user,
            'availableRoles' => $roles,
        ]);
    }
}
