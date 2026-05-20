<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
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
}
