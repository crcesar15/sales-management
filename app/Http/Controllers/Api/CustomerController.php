<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;

final class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService) {}

    public function search(): JsonResponse
    {
        $this->authorize(PermissionsEnum::CUSTOMERS_VIEW);

        $term = request()->string('q')->value();

        $results = $this->customerService->search($term);

        return response()->json($results);
    }

    public function findByTaxId(): JsonResponse
    {
        $this->authorize(PermissionsEnum::CUSTOMERS_VIEW->value, auth()->user());

        $taxId = request()->string('tax_id')->value();

        $customer = $this->customerService->findByTaxId($taxId);

        if (! $customer) {
            return response()->json(null, 404);
        }

        return response()->json([
            'id' => $customer->id,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'tax_id' => $customer->tax_id,
            'tax_id_name' => $customer->tax_id_name,
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize(PermissionsEnum::CUSTOMERS_CREATE->value, auth()->user());

        $customer = $this->customerService->create($request->validated());

        return response()->json([
            'id' => $customer->id,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'tax_id' => $customer->tax_id,
            'tax_id_name' => $customer->tax_id_name,
        ], 201);
    }
}
