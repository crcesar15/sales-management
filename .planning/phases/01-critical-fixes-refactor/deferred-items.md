# Phase 01 — Deferred Items (Out-of-Scope Discoveries)

Items discovered during plan execution that are **out of scope** for the current
plan's changes and were deliberately NOT fixed (per the executor scope-boundary
rule). Logged for a future phase.

## 1. Pre-existing `status='all'` + `withTrashed()` list-filter test failures

- **Discovered during:** Plan 01-06, Task 1 (verification run of related feature tests)
- **Affected tests:**
  - `tests/Feature/BrandTest.php` — "soft-deleted records excluded from default list" (expects `meta.total = 1`, gets 2)
  - `tests/Feature/MeasurementUnitTest.php` — analogous trashed-filter assertion (line 114)
  - `tests/Feature/CategoryTest.php` — analogous trashed-filter assertion
- **Root cause:** Several services (`BrandService`, `MeasurementUnitService`, `CategoryService`) use `->when($status === 'all', fn ($q) => $q->withTrashed())` as their default, so a request with no `status` param returns BOTH active and soft-deleted records. The tests expect the default (no-param) request to exclude soft-deleted records (i.e. behave like `status='active'`).
- **Verified pre-existing:** Stashed plan 01-06 changes and re-ran the tests against the original `app/Services/BrandService.php` — the same 3 failures occur. Plan 01-06 only changed `orderBy`/`orderDirection` handling; the `withTrashed`/`onlyTrashed` lines were preserved verbatim.
- **Why not fixed here:** The `status='all'` semantics are a separate behavior decision (should `all` include trashed, or should the no-param default be `active`?) and touch list-filtering across multiple modules + their controllers and Vue filters. Fixing it belongs in a dedicated filter-semantics phase, not in a sort-whitelist security fix.
- **Suggested future plan:** Standardize list `status` default semantics (decide: `all` = with-trashed vs. default = `active`) across all 11 services + matching controller defaults + Vue filter defaults, and align the feature tests.

## 2. `VariantService::getVariants()` `filter_by` column injection

- **Discovered during:** Plan 01-06, Task 1 (VariantService review)
- **Issue:** `VariantService::getVariants()` passes `$config['filter_by']` directly into `$query->where($config['filter_by'], 'like', $filter)` when `filter_by !== 'name'`. This is a column-name injection vector of the same class as FIX-13, but for the filter field rather than the sort field.
- **Why not fixed here:** FIX-13 is explicitly scoped to `orderBy`/`orderDirection`. Whitelisting `filter_by` is a sibling mitigation that warrants its own requirement (e.g. a future FIX-XX) so it can be applied consistently across every service that accepts a `filter_by`-style parameter, not just `VariantService`.
- **Suggested future plan:** Add a `FILTER_COLUMN_MAP` (or equivalent whitelist) to `VariantService::getVariants()` and any other service accepting a user-supplied filter column.

## 3. `CatalogService::listGroupedByProduct()` is dead code

- **Discovered during:** Plan 01-06, Task 1 (CatalogService review)
- **Issue:** `CatalogService::listGroupedByProduct()` is referenced only in `docs/phase-4-purchasing/...`, not by any controller or test. It is effectively dead code.
- **Action taken:** Left in place but applied the `SORT_COLUMN_MAP` whitelist to it anyway (it is a `list()`-style method in a touched service, and the plan requires "covers its list() methods"). Removing it is a separate cleanup decision.
- **Suggested future plan:** Remove `listGroupedByProduct()` (and its docs) or wire it up if the grouped-by-product catalog view is still wanted.