<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Roles\ListRoleRequest;
use App\Http\Requests\Api\Roles\StoreRoleRequest;
use App\Http\Requests\Api\Roles\UpdateRoleRequest;
use App\Http\Resources\ApiCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    //Get all roles
    public function index(ListRoleRequest $request): ApiCollection
    {
        $request->validated();

        $query = Role::query();

        if ($request->has('filter')) {
            $query->where('name', 'like', $request->input('filter'));
        }

        if ($request->has('include')) {
            $query->with($request->input('include'));
        }

        $query->orderBy($request->input('order_by'), $request->input('order_direction'));

        $response = $query->paginate($request->input('per_page'));

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
    public function store(StoreRoleRequest $request)
    {
        $request->validated();

        $role = DB::transaction(function () use ($request) {
            $role = Role::create([
                'name' => $request->input('name'),
                'guard_name' => 'web',
            ]);

            // Assign permissions
            if ($request->has('permissions')) {
                $role->syncPermissions($request->input('permissions'));
            }

            return $role;
        });

        return response()->json(['data' => $role], 201);
    }

    //Update a role
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $request->validated();

        $updatedRole = DB::transaction(function () use ($request, $role) {
            $role->update([
                'name' => $request->input('name'),
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->input('permissions'));
            }

            return $role;
        });

        return response()->json(['data' => $updatedRole], 200);
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
