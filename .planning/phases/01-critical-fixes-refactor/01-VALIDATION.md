---
phase: 01
slug: critical-fixes-refactor
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-06-21
---

# Phase 01 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 3 with Laravel plugin |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~60–120 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact` and `vendor/bin/phpstan analyse` on changed files.
- **After every plan wave:** Run full `php artisan test --compact` + `composer lint` + `npm run type-check` + `npm run lint`.
- **Before `/gsd-verify-work`:** Full suite must be green.
- **Max feedback latency:** 120 seconds.

---

## Wave 0 Coverage

Wave 0 is implemented by **Plan 01-01**. It creates the four Pest feature-test stubs that later plans will fill in:

- `tests/Feature/Services/Inventory/FifoStockDeductionServiceTest.php` — stubs for FIX-11 / ROADMAP success criterion #3.
- `tests/Feature/CashRegisterShifts/CashRegisterShiftTransitionsTest.php` — stubs for FIX-12 / ROADMAP success criterion #4.
- `tests/Feature/Settings/SettingsCacheTest.php` — stubs for FIX-18 / ROADMAP success criterion #2.
- `tests/Feature/SalesOrders/SalesOrderTaxTest.php` — stubs for ROADMAP success criterion #1 (tax computation).

*Wave 0 is required because the phase's success criteria demand targeted Pest coverage that does not yet exist.*

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 01-01 | 01 | 0 | FIX-11 | T-01-01 | Wave 0 stub for FIFO auto-closes batch at zero and throws `InvalidArgumentException` on insufficient stock for both sale and transfer | feature | `php artisan test --compact --filter=FifoStockDeductionServiceTest` | ✅ Wave 0 | ⬜ pending |
| 01-02 | 01 | 0 | FIX-12 | T-01-02 | Wave 0 stub for `CashRegisterShiftService` valid transitions pass and invalid transitions throw `InvalidArgumentException` | feature | `php artisan test --compact --filter=CashRegisterShiftTransitionsTest` | ✅ Wave 0 | ⬜ pending |
| 01-03 | 01 | 0 | FIX-18 | T-01-03 | Wave 0 stub for `Setting::get()` returns configured value under `CACHE_DRIVER=file` and invalidates stale value on `Setting::set()` | feature | `CACHE_DRIVER=file php artisan test --compact --filter=SettingsCacheTest` | ✅ Wave 0 | ⬜ pending |
| 01-04 | 01 | 0 | FIX-07 | T-01-04 | Wave 0 stub for sales order with `tax_rate=13` saves `tax_amount` equal to `(subTotal - discount) * 13/100` | feature | `php artisan test --compact --filter=SalesOrderTaxTest` | ✅ Wave 0 | ⬜ pending |
| 02-01 | 02 | 1 | FIX-11/21 | T-01-02-S1 | FIFO consolidated into `FifoStockDeductionService`; batches close at zero; insufficient stock throws `InvalidArgumentException`; `recalculateStock()` call sites fixed | feature/unit | `php artisan test --compact --filter=FifoStockDeductionServiceTest` | ✅ stub | ⬜ pending |
| 02-02 | 02 | 1 | FIX-12 | T-01-02-S1 | `CashRegisterShiftService` uses `TRANSITION_MAP` + `validateTransition()` | unit | `php artisan test --compact --filter=CashRegisterShiftTransitionsTest` | ✅ stub | ⬜ pending |
| 02-03 | 02 | 1 | FIX-22 | T-01-02-S2 | Default log stack uses daily; `browser.log` bounded by daily channel | config check | `grep -c "'browser'" config/logging.php` | ⚠️ n/a | ⬜ pending |
| 03-01 | 03 | 2 | FIX-18 | T-01-03-S1 | `Setting` cache uses key-based invalidation; works on `CACHE_DRIVER=file` | feature | `CACHE_DRIVER=file php artisan test --compact --filter=SettingsCacheTest` | ✅ stub | ⬜ pending |
| 03-02 | 03 | 2 | FIX-19/20 | T-01-03-S2 | `ReceptionOrderService` and `SalesOrderService` eager-load relations used by resources | feature | `php artisan test --compact --filter=ReceptionOrder\|SalesOrder` | ⚠️ partial | ⬜ pending |
| 03-03 | 03 | 2 | FIX-16 | T-01-03-S2 | `ReceptionOrderController` calls shared `getClaimedQuantities()` helper | feature | `vendor/bin/phpstan analyse app/Http/Controllers/ReceptionOrderController.php` | ⚠️ partial | ⬜ pending |
| 03-04 | 03 | 2 | FIX-14 (service side) | T-01-03-S3 | Service delete guards throw `InvalidArgumentException` | unit | `grep -c "throw new InvalidArgumentException" app/Services/{Category,Vendor,MeasurementUnit,Brand,Customer,Product}Service.php` | ⚠️ partial | ⬜ pending |
| 04-01 | 04 | 3 | FIX-01/02/09 | T-01-04-A1/A2 | API controllers validate input and authorize every action; `PurchaseOrderResource` returned | feature | `php artisan test --compact --filter=Api` | ⚠️ partial | ⬜ pending |
| 04-02 | 04 | 3 | FIX-03/04/05 | T-01-04-A3 | `CashRegisterShiftResource` returns movements; `StockTransferResource` serializes dates and uses `identifier` | feature | manual smoke / existing API tests | ⚠️ partial | ⬜ pending |
| 04-03 | 04 | 3 | FIX-10 | T-01-04-A3 | `ApiCollection` includes pagination meta matching `UserCollection` | unit | `vendor/bin/phpstan analyse app/Http/Resources/ApiCollection.php` | ⚠️ partial | ⬜ pending |
| 05-01 | 05 | 4 | FIX-14 | T-01-05-S2 | 11 web controllers catch `InvalidArgumentException` only; no generic `Exception` catches remain | feature | `php artisan test --compact --filter=BrandTest\|CategoryTest\|MeasurementUnitTest\|CatalogTest` (use `*Json()` variants; see testing.md known issues) | ⚠️ partial | ⬜ pending |
| 05-02 | 05 | 4 | FIX-15/17/06 | T-01-05-S3 | Dead `StockAdjustment` policy mapping removed; dead `$request->validated()` calls removed; enum comparisons standardized | unit | `vendor/bin/phpstan analyse app/Providers/AuthServiceProvider.php app/Http/Controllers/Api/RoleController.php app/Http/Controllers/Api/MeasurementUnitController.php` | ⚠️ partial | ⬜ pending |
| 06-01 | 06 | 5 | FIX-06/13 | T-01-06-S1/S2 | 11 services whitelist `orderBy` via `SORT_COLUMN_MAP` and validate direction; enum comparisons standardized | unit | `grep -c "SORT_COLUMN_MAP" app/Services/{Role,Category,Variant,Vendor,Store,User,MeasurementUnit,Brand,Customer,Product,Catalog}Service.php` | ⚠️ partial | ⬜ pending |
| 07-01 | 07 | 6 | FIX-07 | T-01-07-F2 | Frontend tax preview and payment-difference validation match backend taxed total | manual / TS check | `npm run type-check` + manual smoke | ⚠️ partial | ⬜ pending |
| 07-02 | 07 | 6 | FIX-08 | T-01-07-F1 | `useApi.ts` no longer sends DOM element as `X-XSRF-TOKEN`; product search returns 200 | manual / feature | `npm run type-check` + manual smoke | ⚠️ partial | ⬜ pending |
| 07-03 | 07 | 6 | FIX-07/11/12/18 | T-01-07-F1/F2 | Targeted Pest tests pass for FIFO, transitions, settings cache, and tax | feature | Four `--filter` test runs green | ✅ stubs | ⬜ pending |
| 07-04 | 07 | 6 | Full phase gate | T-01-07-F1/F2 | Full Pest suite green; `composer lint`; `npm run type-check` and `npm run lint` pass modulo deferred Phase 3 errors | full suite | `php artisan test --compact`, `composer lint`, `npm run type-check`, `npm run lint` | ⚠️ partial | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Frontend tax preview matches backend in browser | FIX-07 / ROADMAP #1 | Requires rendered Vue UI and browser DOM | Open SalesOrders/Create, set subTotal=1000, discount=0, tax_rate=13, assert preview tax=130 and total=1130 |
| Product search via `useApi()` returns 200 | FIX-08 / ROADMAP #5 | Requires running Vite dev server and browser cookies | Open any page that uses product autocomplete, type a query, confirm network response is 200 not 419 |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
