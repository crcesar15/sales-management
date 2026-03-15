# Task 01 — Testing: Stock Overview

## Test File Locations
| File | Type |
|---|---|
| `tests/Feature/Inventory/StockOverviewTest.php` | Feature (HTTP) |
| `tests/Unit/Services/Inventory/StockOverviewServiceTest.php` | Unit |

## Feature Test Cases

**Access control**
- Admin can access `/inventory/stock`
- Sales Rep can access `/inventory/stock`
- Guest is redirected to login

**Stock aggregation**
- Returns correct `global_stock` as SUM of `remaining_quantity` from active + queued batches
- Excludes `closed` batches from stock total
- Returns correct per-store stock breakdown

**Filters**
- Filter by `store_id` returns only variants with stock in that store
- Filter by `category_id` returns only variants in that category
- Filter by `brand_id` returns only matching variants
- `low_stock=true` returns only variants where global stock < `minimum_stock_level`
- `low_stock=true` ignores variants with null `minimum_stock_level`
- Search by product name returns matching results
- Multiple filters combined work correctly

**Pagination**
- Default page size is 25
- Custom `per_page` is respected
- Empty result set returns `data: []` with correct meta

**Variant detail**
- `GET /inventory/stock/{variant}` returns per-store breakdown for that variant
- Returns 404 for non-existent variant

## Unit Test Cases — `StockOverviewService`
- `getPaginatedStock()` builds correct SQL aggregation
- `getVariantStock()` returns empty array when variant has no batches
- Low-stock flag computed correctly (null minimum = no flag)

## Coverage Goals
- [ ] All filter combinations tested
- [ ] Aggregation logic fully covered by unit tests
- [ ] Authorization tested for both roles
- [ ] Edge cases: no batches, all batches closed, null minimum_stock_level
