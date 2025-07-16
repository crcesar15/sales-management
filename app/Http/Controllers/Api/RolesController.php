<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Roles\ListRoleRequest;
use App\Http\Requests\Api\Roles\StoreRoleRequest;
use App\Http\Requests\Api\Roles\UpdateRoleRequest;
use App\Http\Resources\ApiCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class RolesController extends Controller
{
    // Get all roles
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

    // Get a role by id
    public function show($id): JsonResponse
    {
        $role = Role::query()->find($id);
        if ($role) {
            return new JsonResponse(['data' => $role], 200);
        }

        return new JsonResponse(['message' => 'Role not found'], 404);
    }

    // Create a new role
    public function store(StoreRoleRequest $request): JsonResponse
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

        return new JsonResponse(['data' => $role], 201);
    }

    // Update a role
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $request->validated();

        $updatedRole = DB::transaction(function () use ($request, $role): Role {
            $role->update([
                'name' => $request->input('name'),
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->input('permissions'));
            }

            return $role;
        });

        return new JsonResponse(['data' => $updatedRole], 200);
    }

    // Delete a role
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('roles-delete', auth()->user());

        DB::transaction(function () use ($role): void {
            // remove role from users
            $role->users()->detach();

            $role->delete();
        });

        return new JsonResponse(null, 204);
    }
}
