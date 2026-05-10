# Task 03: Catalog — Backend

## Implementation Steps

1. **Service** — Add `CatalogService::listGroupedByProduct()` method that aggregates catalog entries per product variant with eager-loaded vendor, variant, product, and purchase unit relationships
2. **Controller** — Replace legacy `CatalogController` standalone methods with a product-centric `index()` that renders `Catalog/Index` with grouped data; remove `create()`, `edit()`, `update()` standalone methods (create/edit/delete handled by vendor-scoped routes)
3. **Routes** — Simplify standalone catalog routes to a single `GET /catalog` route; keep vendor-scoped routes unchanged
4. **Resource** — Reuse existing `CatalogResource` and `CatalogCollection` from Task 02

## Key Classes / Files

| File                                                  | Purpose                          |
|-------------------------------------------------------|----------------------------------|
| `app/Http/Controllers/CatalogController.php`          | Updated controller (product-centric index) |
| `app/Services/CatalogService.php`                     | New `listGroupedByProduct()` method |
| `app/Http/Resources/Catalog/CatalogResource.php`     | Reused from Task 02              |
| `app/Http/Resources/Catalog/CatalogCollection.php`   | Reused from Task 02              |

## Service Method: `listGroupedByProduct()`

```php
public function listGroupedByProduct(
    string $status = 'active',
    string $orderBy = 'product_name',
    string $orderDirection = 'asc',
    int $perPage = 10,
    ?string $filter = null,
    ?int $vendorId = null,
): LengthAwarePaginator {
    return Catalog::query()
        ->with(['vendor', 'productVariant.product', 'productVariant.values.option', 'unit'])
        ->when($status !== 'all', fn ($q) => $q->where('status', $status))
        ->when($vendorId, fn ($q) => $q->where('vendor_id', $vendorId))
        ->when($filter, fn ($q) => $q->whereHas(
            'productVariant.product',
            fn ($q) => $q->where('name', 'like', "%{$filter}%")
        ))
        ->orderBy($orderBy, $orderDirection)
        ->paginate($perPage)
        ->withQueryString();
}
```

> The grouping by product variant is handled on the frontend (expandable rows), not in the query. The service returns a flat paginated list with eager-loaded relationships; the Vue page groups entries by `product_variant_id`.

## Controller Changes

```php
// CatalogController — replace legacy standalone methods with product-centric index
public function index(): InertiaResponse
{
    $this->authorize(PermissionsEnum::CATALOG_VIEW, auth()->user());
    $catalogEntries = $this->catalogService->listGroupedByProduct(
        status: request('status', 'active'),
        orderBy: request('sortField', 'product_name'),
        orderDirection: request('sortDirection', 'asc'),
        perPage: request('per_page', 10),
        filter: request('filter'),
        vendorId: request('vendor_id'),
    );

    return Inertia::render('Catalog/Index', [
        'catalogEntries' => new CatalogCollection($catalogEntries),
        'filters' => [
            'filter' => request('filter'),
            'status' => request('status', 'active'),
        ],
    ]);
}
```

## Routes

```php
// Standalone catalog — product-centric view only
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');

// Vendor-scoped catalog — unchanged (Task 02)
Route::resource('vendors.catalog', ...); // create, edit, store, update, destroy
```

## Important Patterns
- Create/edit/delete actions redirect to vendor-scoped routes, not standalone catalog routes
- The `listGroupedByProduct()` method is essentially the same as `list()` from Task 02 but with a different default sort (product name) and the intent of grouping by variant on the frontend
- Authorization uses existing `CATALOG_VIEW` permission for listing

## Packages
- `spatie/laravel-permission` — gate with `catalog.view`