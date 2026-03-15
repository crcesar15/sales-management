# Phase 6 — Task 02: Sales Reports — Backend

## Key Implementation Steps

1. **Gate access** — `$this->authorize('viewAny', SalesReport::class)` or middleware `permission:reports.view_own|reports.view_all`
2. **Create `SalesReportController`** — `index` action; delegates to `SalesReportService`
3. **Create `SalesReportService`** with:
   - `getMetrics(User, SalesReportFilterDTO): array`
   - `getOrders(User, SalesReportFilterDTO): LengthAwarePaginator`
4. **Create `SalesReportFilterDTO`** — typed value object holding all filter params
5. **Create `SalesReportFilterRequest`** — validates and transforms query params into DTO
6. **Enforce permission scoping** in service:
   - `view_own` → always force `user_id = auth()->id()`
   - `view_all` → use passed filters freely
7. **Build base query** using chained `when()` calls (avoid N+1 — eager load `customer`, `user`)
8. **Compute average order value** safely: `$count > 0 ? $total / $count : 0`
9. **Compute refunds** as sum of `total` where `status = 'refunded'` from same base query
10. **Return Inertia response** with `filters`, `metrics`, `orders` props

## Key Classes / Files

| File | Purpose |
|---|---|
| `app/Http/Controllers/Reports/SalesReportController.php` | Inertia render |
| `app/Services/Reports/SalesReportService.php` | Query logic |
| `app/DTOs/SalesReportFilterDTO.php` | Typed filter container |
| `app/Http/Requests/Reports/SalesReportFilterRequest.php` | Validation + DTO construction |
| `routes/web.php` | `Route::get('/reports/sales', ...)` under `reports` prefix group |

## Important Patterns

```php
// SalesReportFilterDTO
final class SalesReportFilterDTO
{
    public function __construct(
        public readonly ?int    $storeId,
        public readonly ?int    $userId,
        public readonly ?int    $variantId,
        public readonly Carbon  $dateFrom,
        public readonly Carbon  $dateTo,
        public readonly ?string $paymentMethod,
        public readonly ?string $status,
    ) {}
}
```

```php
// Permission-scoped base query
private function baseQuery(User $user, SalesReportFilterDTO $dto): Builder
{
    return SalesOrder::query()
        ->when(
            $user->cannot('reports.view_all'),
            fn($q) => $q->where('user_id', $user->id),
            fn($q) => $q->when($dto->userId, fn($q) => $q->where('user_id', $dto->userId))
        )
        ->when($dto->storeId,       fn($q) => $q->where('store_id',      $dto->storeId))
        ->when($dto->status,        fn($q) => $q->where('status',         $dto->status))
        ->when($dto->paymentMethod, fn($q) => $q->where('payment_method', $dto->paymentMethod))
        ->whereBetween('created_at', [$dto->dateFrom, $dto->dateTo]);
}
```

## Gotchas
- Clone base query before calling aggregate methods — Eloquent modifies the query builder in place
- `withCount('items')` adds `items_count` to each model without loading all items
- URL query param `user_id` must be stripped / overridden for `view_own` users server-side
- Date range defaults: if no `date_from`, default to start of current month
