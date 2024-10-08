<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    //Get all roles
    public function index(Request $request)
    {
        $query = Role::query();

        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter) {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        $query->withCount('users');

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'name';
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
        $role = Role::find($id);
        if ($role) {
            return response()->json(['data' => $role], 200);
        } else {
            return response()->json(['message' => 'Role not found'], 404);
        }
    }

    //Create a new role
    public function store(Request $request)
    {
        $role = Role::create($request->all());

        return response()->json(['data' => $role], 201);
    }

    //Update a role
    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if ($role) {
            $role->update($request->all());

            return response()->json(['data' => $role], 200);
        } else {
            return response()->json(['message' => 'Role not found'], 404);
        }
    }

    //Delete a role
    public function destroy($id)
    {
        $role = Role::find($id);
        if ($role) {
            //remove the role from the users
            $role->users()->update(['role_id' => null]);

            $role->delete();

            return response()->json(['data' => $role], 200);
        } else {
            return response()->json(['message' => 'Role not found'], 404);
        }
    }
}
