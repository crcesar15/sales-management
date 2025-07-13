<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\ListUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;
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
    public function update(Request $request, $id)
    {
        // check if the username already exists
        $user = User::where(
            [
                ['username', '=', $request->input('username')],
                ['id', '!=', $id],
            ]
        )->first();

        if ($user) {
            return response()->json(['message' => 'Username is not available'], 400);
        }

        // check if the email already exists
        $user = User::where(
            [
                ['email', '=', $request->input('email')],
                ['id', '!=', $id],
            ]
        )->first();

        if ($user) {
            return response()->json(['message' => 'Email is not available'], 400);
        }

        // update user
        $user = User::find($id);

        if ($user) {
            $user->update($request->all());

            return response()->json(['data' => $user], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
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
