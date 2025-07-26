<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\ListUserRequest;
use App\Http\Requests\Api\Users\StoreUserRequest;
use App\Http\Requests\Api\Users\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class UserController extends Controller
{
    public function index(ListUserRequest $request): UserCollection
    {
        $request->validated();

        $query = User::query();

        if ($request->has('filter')) {
            $query->where('name', 'like', $request->string('filter')->value());
        }

        if ($request->has('status')) {
            if ($request->string('status')->value() === 'archived') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->string('status')->value());
            }
        }

        if ($request->has('include')) {
            /** @var array<string> $include */
            $include = $request->array('include');

            $query->with($include);
        }

        $query->orderBy($request->string('order_by')->value(), $request->string('order_direction')->value());

        $response = $query->paginate($request->integer('per_page'));

        return new UserCollection($response);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user, 200);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($request, $validated) {
            // Create a new user
            $user = User::query()->create($validated);

            // Assign roles if provided
            if ($request->has('roles')) {
                /** @var array<string> $roles */
                $roles = $request->input('roles');

                $user->syncRoles($roles);
            }

            return $user;
        });

        return response()->json($user, 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $updateUser = DB::transaction(function () use ($request, $user, $validated): User {
            // Create a new user
            $user->update($validated);

            // Assign roles if provided
            if ($request->has('roles')) {
                /** @var array<string> */
                $roles = $request->input('roles');

                $user->syncRoles($roles);
            }

            return $user;
        });

        return response()->json($updateUser, 200);
    }

    public function destroy(User $user): Response
    {
        $this->authorize('users-delete', auth()->user());

        // Inactive user
        $user->update(['status' => 'archived']);

        // soft delete
        $user->delete();

        return response()->noContent();
    }

    public function restore(User $user): Response
    {
        $this->authorize('users-edit', auth()->user());

        $user->restore();

        $user->update(['status' => 'active']);

        return response()->noContent();
    }
}
