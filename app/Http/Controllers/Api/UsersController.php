<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    //Get all users
    public function index(Request $request)
    {
        $query = User::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter) {
                    $query->where('first_name', 'like', $filter);
                }
            );
        }

        $status = $request->input('status', 'all');

        if ($status === 'archived') {
            $query->onlyTrashed();
        } else {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $includes = $request->input('includes', '');

        if (!empty($includes)) {
            $query->with(explode(',', $includes));
        }

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'first_name';
        $order_direction = $request->has('order_direction')
            ? $request->get('order_direction')
            : 'ASC';

        $response = $query->orderBy(
            $request->input('order_by', $order_by),
            $request->input('order_direction', $order_direction)
        )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
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
    public function store(Request $request)
    {
        // check if the username already exists
        $user = User::where('username', $request->input('username'))->first();

        if ($user) {
            return response()->json(['message' => 'Username is not available'], 400);
        }

        // check if the email already exists
        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            return response()->json(['message' => 'Email is not available'], 400);
        }

        // create a new user
        $user = User::create($request->all());

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
