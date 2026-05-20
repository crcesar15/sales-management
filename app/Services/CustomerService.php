<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CustomerService
{
    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'first_name',
        string $orderDirection = 'asc',
        int $perPage = 20,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return Customer::query()
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->search((string) $filter)
            )
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->withCount('salesOrders')
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            return Customer::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $customer->update($data);

            return $customer;
        });
    }

    public function delete(Customer $customer): void
    {
        if ($customer->hasSalesOrders()) {
            throw new Exception('Cannot delete customer: it has associated sales orders.');
        }

        DB::transaction(fn () => $customer->delete());
    }

    /**
     * Search for POS: returns matching customers for a search term.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function search(string $term, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::query()
            ->where('status', 'active')
            ->search($term)
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
