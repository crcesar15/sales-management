<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'name',
        string $orderDirection = 'asc',
        int $perPage = 20,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return Category::query()
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where('name', 'like', "%{$filter}%")
            )
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->withCount('products')
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data): Category {
            return Category::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $category->update($data);

            return $category;
        });
    }

    public function delete(Category $category): void
    {
        if ($category->hasActiveProducts()) {
            throw new Exception('Cannot delete category: it is assigned to one or more active products.');
        }

        DB::transaction(fn () => $category->delete());
    }

    public function restore(Category $category): void
    {
        DB::transaction(fn () => $category->restore());
    }
}
