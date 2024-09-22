<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;

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
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // Include the user role with media
        $user->load(['role']);

        // List of Roles
        $roles = Brand::all();

        return Inertia::render('users/edit/index', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }
}
