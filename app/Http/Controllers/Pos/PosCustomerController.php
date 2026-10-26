<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\Customer\CustomerCollection;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;

final class PosCustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);
        $this->authorize(PermissionsEnum::CUSTOMERS_VIEW);

        $status = request()->string('status', 'active')->value();

        $customers = $this->customerService->list(
            status: $status,
            orderBy: request()->string('order_by', 'tax_id_name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('Pos/Customers/Index', [
            'customers' => new CustomerCollection($customers),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'tax_id_name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);
        $this->authorize(PermissionsEnum::CUSTOMERS_CREATE);

        return Inertia::render('Pos/Customers/Create/Index');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);
        $this->customerService->create($request->validated());

        return redirect()->route('pos.customers.index');
    }

    public function edit(Customer $customer): InertiaResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);
        $this->authorize(PermissionsEnum::CUSTOMERS_EDIT);

        return Inertia::render('Pos/Customers/Edit/Index', ['customer' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);
        $this->customerService->update($customer, $request->validated());

        return redirect()->route('pos.customers.index');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);
        $this->authorize(PermissionsEnum::CUSTOMERS_DELETE);

        try {
            $this->customerService->delete($customer);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['customer' => $e->getMessage()]);
        }

        return redirect()->route('pos.customers.index');
    }
}
