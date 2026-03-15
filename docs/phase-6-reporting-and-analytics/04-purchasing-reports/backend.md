# Phase 6 — Task 04: Purchasing Reports — Backend

## Key Implementation Steps

1. **Gate to Admin only** — apply `permission:reports.view_all` middleware to all purchasing report routes
2. **Create `PurchasingReportController`** — `index` action renders Inertia page with deferred props per section
3. **Create `PurchasingReportService`** with:
   - `getPoSummary(PurchasingFilterDTO): array` — metrics + paginated PO list
   - `getVendorSpend(DateRangeDTO): Collection` — aggregated per vendor
   - `getReceptionAccuracy(AccuracyFilterDTO): LengthAwarePaginator` — line-item comparison
4. **Create filter DTOs** for each section
5. **Lead time calculation** — `DATEDIFF(received_at, created_at)` in SQL; exclude `null` received_at rows from average
6. **Vendor Spend** — single aggregation query grouped by `vendor_id`; join `vendors` for name
7. **Reception Accuracy** — raw DB join across `purchase_order_items` ↔ `reception_order_product`; compute `variance` and `variance_pct` in SQL
8. **Variance type filter** — `over` = `qty_received > qty_ordered`, `under` = opposite, `exact` = equal
9. **Register routes** under `/reports/purchasing` in `routes/web.php` with auth + permission middleware
10. **Add `received_at` column** to `purchase_orders` if not present from Phase 5 (verify migration exists)

## Key Classes / Files

| File | Purpose |
|---|---|
| `app/Http/Controllers/Reports/PurchasingReportController.php` | Inertia render |
| `app/Services/Reports/PurchasingReportService.php` | All section queries |
| `app/DTOs/PurchasingFilterDTO.php` | PO summary filter params |
| `app/DTOs/AccuracyFilterDTO.php` | Reception accuracy filter params |

## Important Patterns

```php
// Lead time — safe average with null guard
$avgLeadTime = PurchaseOrder::whereNotNull('received_at')
    ->when($dto->vendorId, fn($q) => $q->where('vendor_id', $dto->vendorId))
    ->whereBetween('created_at', [$dto->dateFrom, $dto->dateTo])
    ->selectRaw('AVG(DATEDIFF(received_at, created_at)) as avg_days')
    ->value('avg_days');
// Returns null if no received POs — handle in template
```

```php
// Reception accuracy variance percentage
->selectRaw('
    poi.quantity_ordered,
    SUM(rop.quantity_received)                                              AS qty_received,
    SUM(rop.quantity_received) - poi.quantity_ordered                      AS variance,
    ROUND(
        ((SUM(rop.quantity_received) - poi.quantity_ordered) / poi.quantity_ordered) * 100,
        2
    )                                                                       AS variance_pct
')
->groupBy('poi.id')
```

## Gotchas
- A single PO item may have quantities received across multiple receptions — aggregate `SUM(rop.quantity_received)` grouped by `poi.id`
- `received_at` on `purchase_orders` may not exist if Phase 5 used only `reception_orders.created_at` — verify and add migration if needed
- Variance percentage division by zero if `quantity_ordered = 0` — guard with `NULLIF(poi.quantity_ordered, 0)`
- Vendor Spend aggregates by `po.created_at` date, not reception date — be explicit in the UI
