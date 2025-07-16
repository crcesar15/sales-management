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
use Illuminate\Support\Facades\DB;

final class UsersController extends Controller
{
    // Get all users
    public function index(ListUserRequest $request): UserCollection
    {
        $request->validated();

        $query = User::query();

        if ($request->has('filter')) {
            $query->where('name', 'like', $query->input('filter'));
        }

        if ($request->has('status')) {
            if ($request->input('status') === 'archived') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->has('include')) {
            $query->with($request->input('include'));
        }

        $query->orderBy($request->input('order_by'), $request->input('order_direction'));

        $response = $query->paginate($request->input('per_page', 10));

        return new UserCollection($response);
    }

    // Get a user by id
    public function show($id): JsonResponse
    {
        $user = User::query()->find($id);
        if ($user) {
            return new JsonResponse(['data' => $user], 200);
        }

        return new JsonResponse(['message' => 'User not found'], 404);
    }

    // Create a new user
    public function store(StoreUserRequest $request): JsonResponse
    {
        $request->validated();

        $user = DB::transaction(function () use ($request) {
            // Create a new user
            $user = User::query()->create($request->all());

            // Assign roles if provided
            if ($request->has('roles')) {
                $user->syncRoles($request->input('roles'));
            }

            return $user;
        });

        return new JsonResponse(['data' => $user], 201);
    }

    // Update a user
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $request->validated();

        $updateUser = DB::transaction(function () use ($request, $user): User {
            // Create a new user
            $user->update($request->all());

            // Assign roles if provided
            if ($request->has('roles')) {
                $user->syncRoles($request->input('roles'));
            }

            return $user;
        });

        return new JsonResponse(['data' => $updateUser], 200);
    }

    // Delete a user
    public function destroy(User $user)
    {
        $this->authorize('users-delete', auth()->user());

        // Inactive user
        $user->update(['status' => 'archived']);

        // soft delete
        $user->delete();

        return response(null, 204);
    }

    // Restore a user, search deleted_at
    public function restore(User $user)
    {
        $this->authorize('users-edit', auth()->user());

        $user->restore();

        $user->update(['status' => 'active']);

        return response(null, 204);
    }
}
