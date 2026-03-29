<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\User\UserCollection;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_VIEW, auth()->user());

        $status = request()->string('status', 'active')->value();

        $users = $this->userService->list(
            status: $status,
            orderBy: request()->string('order_by', 'first_name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 10),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('Users/Index', [
            'users' => new UserCollection($users),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'first_name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 10),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_CREATE, auth()->user());

        return Inertia::render('Users/Create/Index', [
            'availableRoles' => Role::all(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->route('users');
    }

    public function edit(User $user): InertiaResponse
    {
        $this->authorize(PermissionsEnum::USERS_EDIT, auth()->user());

        $user->load(['roles' => function (MorphToMany $query) {
            $query->select('id', 'name');
        }]);

        $user->makeHidden(['password', 'remember_token']);

        return Inertia::render('Users/Edit/Index', [
            'user' => $user,
            'availableRoles' => Role::all(['id', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('users');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize(PermissionsEnum::USERS_DELETE, auth()->user());

        $this->userService->delete($user);

        return redirect()->route('users');
    }

    public function restore(User $user): RedirectResponse
    {
        $this->authorize(PermissionsEnum::USERS_EDIT, auth()->user());

        $user->restore();
        $user->update(['status' => 'active']);

        return redirect()->route('users');
    }
}
