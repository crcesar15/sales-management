# Task 01 — Backend: Stock Overview

## Key Implementation Steps
1. Create migration `add_store_id_to_batches_table`
2. Update `Batch` model: add `store_id` to `$fillable`, add `store()` belongsTo relationship
3. Create `StockOverviewController` (read-only, two actions: `index`, `show`)
4. Create `StockOverviewService` — encapsulates aggregation queries
5. Create `StockOverviewResource` (or inline Inertia props) for response shaping
6. Add routes in `routes/web.php` under `inventory` prefix + middleware group
7. Register `store_id` in `Batch` model factory (Phase 4 will use this)

## Key Classes / Files
| File | Purpose |
|---|---|
| `database/migrations/..._add_store_id_to_batches_table.php` | Add FK column |
| `app/Models/Batch.php` | Add `store()` relation, update `$fillable` |
| `app/Http/Controllers/Inventory/StockOverviewController.php` | Index + show |
| `app/Services/Inventory/StockOverviewService.php` | Aggregation logic |
| `routes/web.php` | Route definitions |

## StockOverviewService — Key Methods
```php
// Returns paginated collection: variant + per-store stock + global stock
public function getPaginatedStock(array $filters, int $perPage): LengthAwarePaginator

// Returns per-store breakdown for a single variant
public function getVariantStock(ProductVariant $variant): array
```

## Important Patterns
- Use a single query with `GROUP BY product_variant_id, store_id` — avoid N+1
- Eager load `productVariant.product`, `productVariant.attributeValues`, `store`
- Apply filters via query scopes or inline `when()` clauses
- Compute `low_stock` flag in PHP after fetching (avoids complex SQL CASE in filters)

## Packages Used
- `spatie/laravel-activitylog` — not needed for read-only view
- `spatie/laravel-permission` — middleware: `auth` only (no permission gate)

## Gotchas
- `minimum_stock_level` column does not exist until Task 05 migration — use `null` check
- Closed batches must be excluded: `whereIn('status', ['active', 'queued'])`
- Global stock ≠ sum of `initial_quantity`; always use `remaining_quantity`
- If `store_id` FK is added after data exists, ensure existing batches get a store_id or allow nullable temporarily
