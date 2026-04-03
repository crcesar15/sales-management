<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Store\CreateStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Http\Resources\Store\StoreCollection;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class StoreController extends Controller
{
    private readonly StoreService $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STORE_VIEW);

        $status = request()->string('status', 'active')->value();

        $stores = $this->storeService->list(
            status: $status,
            orderBy: request()->string('order_by', 'name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('Stores/Index', [
            'stores' => new StoreCollection($stores),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STORE_CREATE);

        return Inertia::render('Stores/Create/Index');
    }

    public function store(CreateStoreRequest $request): RedirectResponse
    {
        $this->storeService->create($request->validated());

        return redirect()->route('stores');
    }

    public function edit(Store $store): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STORE_EDIT);

        $store->load(['users.roles']);
        $availableUsers = User::query()
            ->where('status', 'active')
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ]);

        return Inertia::render('Stores/Edit/Index', [
            'store' => $store,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $this->storeService->update($store, $request->validated());

        return redirect()->route('stores');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize(PermissionsEnum::STORE_DELETE);

        $this->storeService->delete($store);

        return redirect()->route('stores');
    }

    public function restore(Store $store): RedirectResponse
    {
        $this->authorize(PermissionsEnum::STORE_RESTORE);

        $this->storeService->restore($store);

        return redirect()->route('stores');
    }

    public function updateStatus(Store $store): RedirectResponse
    {
        $this->authorize(PermissionsEnum::STORE_EDIT);

        $status = request()->string('status')->value();

        $this->storeService->updateStatus($store, $status);

        return redirect()->back();
    }
}
