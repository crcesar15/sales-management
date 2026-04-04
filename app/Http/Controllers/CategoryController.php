<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\Category\CategoryCollection;
use App\Models\Category;
use App\Services\CategoryService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CategoryController extends Controller
{
    private readonly CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATEGORIES_VIEW);

        $status = request()->string('status', 'active')->value();

        $categories = $this->categoryService->list(
            status: $status,
            orderBy: request()->string('order_by', 'name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('Categories/Index', [
            'categories' => new CategoryCollection($categories),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create($request->validated());

        return redirect()->route('categories');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('categories');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize(PermissionsEnum::CATEGORIES_DELETE);

        try {
            $this->categoryService->delete($category);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('categories');
    }

    public function restore(Category $category): RedirectResponse
    {
        $this->authorize(PermissionsEnum::CATEGORIES_RESTORE);

        $this->categoryService->restore($category);

        return redirect()->route('categories');
    }
}
