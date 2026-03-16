<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\AssignStoreRequest;
use App\Http\Requests\Api\Users\ListUserRequest;
use App\Http\Requests\Api\Users\StoreUserRequest;
use App\Http\Requests\Api\Users\UpdateUserRequest;
use App\Http\Requests\Api\Users\UpdateUserStatusRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * Paginated list with search/filters.
     */
    public function index(ListUserRequest $request): UserCollection
    {
        $request->validated();

        $query = User::query()->with(['roles', 'stores']);

        // ?search= (documented) or ?filter= (legacy alias, normalised in request)
        $searchTerm = $request->string('filter')->value()
            ?: $request->string('search')->value();

        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm): void {
                $like = '%' . $searchTerm . '%';
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        // ?status=
        if ($request->has('status')) {
            if ($request->string('status')->value() === 'archived') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $request->string('status')->value());
            }
        }

        // ?store_id=
        if ($request->has('store_id')) {
            $query->whereHas('stores', function ($q) use ($request): void {
                $q->where('stores.id', $request->integer('store_id'));
            });
        }

        $query->orderBy(
            $request->string('order_by')->value(),
            $request->string('order_direction')->value()
        );

        return new UserCollection(
            $query->paginate($request->integer('per_page'))
        );
    }

    /**
     * Single user details.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'stores']);

        return new UserResource($user);
    }

    /**
     * Create user, assign role and stores.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update profile.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->userService->update($user, $request->validated());

        return new UserResource($user);
    }

    /**
     * Soft delete user.
     */
    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return response()->noContent();
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user): Response
    {
        $this->authorize('restore', $user);

        $user->restore();
        $user->update(['status' => 'active']);

        return response()->noContent();
    }

    /**
     * Update status only.
     */
    public function updateStatus(UpdateUserStatusRequest $request, User $user): UserResource
    {
        $user = $this->userService->updateStatus($user, $request->validated('status'));

        return new UserResource($user);
    }

    /**
     * Assign a store to the user.
     */
    public function assignStore(AssignStoreRequest $request, User $user): UserResource
    {
        $this->authorize('manageStores', $user);

        $validated = $request->validated();
        $store = Store::findOrFail($validated['store_id']);
        $roleId = isset($validated['role_id']) ? (int) $validated['role_id'] : null;

        $user = $this->userService->assignStore($user, $store, $roleId);

        return new UserResource($user);
    }

    /**
     * Remove a store assignment from the user.
     */
    public function removeStore(User $user, Store $store): Response
    {
        $this->authorize('manageStores', $user);

        $this->userService->removeStore($user, $store);

        return response()->noContent();
    }
}
