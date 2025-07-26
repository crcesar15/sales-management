<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
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
    public function index(ListRoleRequest $request): ApiCollection
    {
        $request->validated();

        $query = Role::query();

        if ($request->has('filter')) {
            $query->where('name', 'like', $request->string('filter')->value());
        }

        if ($request->has('include')) {
            /** @var array<string> $include */
            $include = $request->array('include');
            $query->with($include);
        }

        $query->orderBy(
            $request->string('order_by')->value(),
            $request->string('order_direction')->value()
        );

        $response = $query->paginate($request->integer('per_page'));

        return new ApiCollection($response);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize(PermissionsEnum::ROLES_VIEW, auth()->user());

        return response()->json($role, 200);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $request->validated();

        $role = DB::transaction(function () use ($request): Role {
            $role = Role::create([
                'name' => $request->input('name'),
                'guard_name' => 'web',
            ]);

            // Assign permissions
            if ($request->has('permissions')) {
                $role->syncPermissions($request->array('permissions'));
            }

            return $role;
        });

        return response()->json($role, 201);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $request->validated();

        $updatedRole = DB::transaction(function () use ($request, $role): Role {
            $role->update([
                'name' => $request->input('name'),
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->array('permissions'));
            }

            return $role;
        });

        return response()->json($updatedRole, 200);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize(PermissionsEnum::ROLES_DELETE, auth()->user());

        DB::transaction(function () use ($role): void {
            // remove role from users
            $role->users()->detach();

            $role->delete();
        });

        return new JsonResponse(null, 204);
    }
}
