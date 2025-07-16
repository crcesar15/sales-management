<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\ListUserRequest;
use App\Http\Requests\Api\Users\StoreUserRequest;
use App\Http\Requests\Api\Users\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    //Get all users
    public function index(ListUserRequest $request)
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

    //Get a user by id
    public function show($id)
    {
        $user = User::find($id);
        if ($user) {
            return response()->json(['data' => $user], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    //Create a new user
    public function store(StoreUserRequest $request)
    {
        $request->validated();

        $user = DB::transaction(function () use ($request) {
            // Create a new user
            $user = User::create($request->all());

            // Assign roles if provided
            if ($request->has('roles')) {
                $user->syncRoles($request->input('roles'));
            }

            return $user;
        });

        return response()->json(['data' => $user], 201);
    }

    //Update a user
    public function update(UpdateUserRequest $request, User $user)
    {
        $request->validated();

        $updateUser = DB::transaction(function () use ($request, $user) {
            // Create a new user
            $user->update($request->all());

            // Assign roles if provided
            if ($request->has('roles')) {
                $user->syncRoles($request->input('roles'));
            }

            return $user;
        });

        return response()->json(['data' => $updateUser], 200);
    }

    //Delete a user
    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            // Inactive user
            $user->update(['status' => 'archived']);

            // soft delete
            $user->delete();
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    //Restore a user
    public function restore($id)
    {
        $user = User::withTrashed()->find($id);
        if ($user) {
            $user->restore();

            $user->update(['status' => 'active']);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }
}
