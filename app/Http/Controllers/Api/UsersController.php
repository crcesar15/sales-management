<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    //Get all roles
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

    //Get a role by id
    public function show($id)
    {
        $user = User::find($id);
        if ($user) {
            return response()->json(['data' => $user], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    //Create a new role
    public function store(Request $request)
    {
        $user = User::create($request->all());

        return response()->json(['data' => $user], 201);
    }

    //Update a role
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update($request->all());

            return response()->json(['data' => $user], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    //Delete a role
    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            //remove the role from the users
            $user->users()->update(['role_id' => null]);

            $user->delete();

            return response()->json(['data' => $user], 200);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }
}
