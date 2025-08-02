<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_VIEW, auth()->user());

        return Inertia::render('Users/Index');
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_CREATE, auth()->user());

        // List of Roles
        $roles = Role::all();

        return Inertia::render('Users/Create/Index', [
            'availableRoles' => $roles,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_EDIT, auth()->user());

        // Include the user role with only id and name without pivot
        $user->load(['roles' => function (MorphToMany $query) {
            $query->select('id', 'name');
        }]);

        // remove password and remember_token from the user object
        $user->makeHidden(['password', 'remember_token']);

        // List of Roles
        $roles = Role::all();

        return Inertia::render('Users/Edit/Index', [
            'user' => $user,
            'availableRoles' => $roles,
        ]);
    }
}
