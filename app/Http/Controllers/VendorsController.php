<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Vendors\StoreVendorRequest;
use App\Http\Requests\Vendors\UpdateVendorRequest;
use App\Http\Resources\Vendor\VendorCollection;
use App\Models\Vendor;
use App\Services\VendorService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class VendorsController extends Controller
{
    private readonly VendorService $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_VIEW);

        $status = request()->string('status', 'all')->value();

        $vendors = $this->vendorService->list(
            status: $status,
            orderBy: request()->string('order_by', 'fullname')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('Vendors/Index', [
            'vendors' => new VendorCollection($vendors),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'fullname')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_CREATE);

        return Inertia::render('Vendors/Create/Index');
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $this->vendorService->create($request->validated());

        return redirect()->route('vendors');
    }

    public function edit(Vendor $vendor): InertiaResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_EDIT);

        return Inertia::render('Vendors/Edit/Index', ['vendor' => $vendor]);
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->vendorService->update($vendor, $request->validated());

        return redirect()->route('vendors');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_DELETE);

        try {
            $this->vendorService->delete($vendor);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('vendors');
    }

    public function products(Vendor $vendor): InertiaResponse
    {
        $this->authorize(PermissionsEnum::VENDORS_VIEW);

        return Inertia::render('Vendors/Products/Index', ['vendor' => $vendor]);
    }
}
