# Phase 6 — Task 03: Inventory Reports — Backend

## Key Implementation Steps

1. **Create `InventoryReportController`** — single `index` action; renders Inertia page with deferred props for each section
2. **Create `InventoryReportService`** with three public methods:
   - `getStockLevels(User, StockLevelFilterDTO): LengthAwarePaginator`
   - `getBatchStatus(User, BatchFilterDTO): LengthAwarePaginator`
   - `getStockMovements(User, MovementFilterDTO): LengthAwarePaginator`
3. **Create filter DTOs** for each section (or a single combined DTO with nullable sections)
4. **Create `StockMovementQuery` class** — encapsulates the UNION query across 4 tables
5. **Scope by role** — `view_own` forces `store_id` to user's store; `view_all` allows free filtering
6. **Stock Levels query** — eager load `product.category`, `product.brand`; filter with `when()`
7. **Batch Status query** — filter by `expiry_date` range and `status`
8. **Movement UNION** — use `DB::table()->union()` chaining; wrap in subquery for `orderBy` + `paginate`
9. **Register routes** under `reports` middleware group with `permission:reports.view_all|reports.view_own`
10. **Defer chart/heavy props** — wrap movement history in `Inertia::defer()` if initial load is slow

## Key Classes / Files

| File | Purpose |
|---|---|
| `app/Http/Controllers/Reports/InventoryReportController.php` | Inertia render |
| `app/Services/Reports/InventoryReportService.php` | Section query logic |
| `app/Queries/StockMovementQuery.php` | UNION query builder for movement history |
| `app/DTOs/StockLevelFilterDTO.php` | Stock levels filter params |
| `app/DTOs/BatchStatusFilterDTO.php` | Batch filter params |
| `app/DTOs/MovementFilterDTO.php` | Movement history filter params |

## Important Patterns

```php
// StockMovementQuery — UNION builder
class StockMovementQuery
{
    public function build(MovementFilterDTO $dto): Builder
    {
        $sales      = $this->salesQuery($dto);
        $adjustments = $this->adjustmentsQuery($dto);
        $receptions = $this->receptionsQuery($dto);
        $transfers  = $this->transfersQuery($dto);

        return $sales
            ->union($adjustments)
            ->union($receptions)
            ->union($transfers);
    }
}
```

```php
// Controller — deferred sections
return Inertia::render('Reports/Inventory/Index', [
    'stockLevels' => Inertia::defer(fn() => $service->getStockLevels($user, $stockDto)),
    'batches'     => Inertia::defer(fn() => $service->getBatchStatus($user, $batchDto)),
    'movements'   => Inertia::defer(fn() => $service->getStockMovements($user, $movDto)),
]);
```

## Gotchas
- `DB::table()->union()` loses Eloquent model hydration — map raw results to plain objects/arrays
- UNION requires identical column counts and aliases across all sub-queries
- Paginating a UNION requires wrapping in a subquery: `DB::query()->fromSub($union, 'movements')->paginate(25)`
- `view_own` store scoping must be applied inside each branch of the UNION independently
