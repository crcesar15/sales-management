# Retail dashboard

## Scope

Primary target: `resources/js/Pages/Dashboard/Index.vue`, served by `home`.
Visitor mode: Operate. Audience: retail owners and operations managers.

Confirmed decisions: mixed retail; combined and per-store views; overview first;
today compared with the same weekday last week at the same time; fulfillment-based
sales; historical gross profit; five-minute visible-page refresh; explicit profit
permission; disposable development data. The new permission covers dashboard
profitability and new historical costs. Existing purchasing permissions retain
their meaning.

## Direction contract

THESIS: An overview of the shop's actual trading and immediate operational needs.
Lead with a compact aligned summary, not decorative metric cards.

OWN-WORLD: Inherit DESIGN.md: Lato, Operational Navy, PrimeVue Aura, flat bordered
surfaces, labelled semantic status, first-class light/dark themes.

STORY: Read today's sales and gross profit, compare trading pace, identify stock
risks, then inspect collections and shift exceptions within the same store scope.

FIRST VIEWPORT: Page title and store/period/refresh controls; a single summary
strip; a wide sales trend beside prioritized inventory attention. Product and
cash/shift detail follows, with a store breakdown in the combined view. The
signature interaction is selecting a store row to scope the entire overview.

FORM: Overview-first operational composition explicitly selected by the user.
seed: none (user-pinned composition).

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

## Calculation contract

- Sales use fulfilled_at and fulfilled/completed orders; net of discounts and tax.
- Payments use their receipt timestamp; prepayments and later credit collections
  are collections, not a second sale. Collections include tax.
- Gross profit uses frozen allocation costs and deterministic line discount
  allocation. Null cost is unknown; zero is valid. Incomplete cost coverage makes
  total profit/margin unavailable rather than overstated.
- Inventory and open shifts are current snapshots, independent of report dates.
- Stock risks are evaluated per variant/store before consolidation.
- Closed-shift discrepancies use closed_at, with shortages and overages separate.
- Reporting uses the configured business timezone and half-open query intervals.

## Implementation sequence

1. Cost schema, precise amount allocation, scope resolution, profit permission.
2. Capture receipt/opening costs, preserve transfers, snapshot fulfillment costs.
3. Authorized aggregates and atomic Inertia dashboard snapshot.
4. Matching sales, stock-alert and shift drill-downs.
5. Typed Vue components, translations, accessible chart, five-minute refresh.
6. Financial/authorization regression, development reset, quality checks and
   desktop/mobile light/dark visual review.

## Verification

Test multi-batch and pack costing, transfers, discounts, unknown/zero cost,
fulfillment vs payment timing, store/personal scope, permission revocation,
timezone boundaries, alert consolidation and shift reconciliation. Run Pest,
composer lint, frontend lint/type-check/build; inspect the rendered page.

Development schema changes modify existing migrations. Rebuild only the confirmed
disposable development database with migrate:fresh --seed --no-interaction.
Demo fixtures are opt-in via DashboardDemoSeeder and explicitly synthetic.

## Implementation handoff

### Entry point and authorization

`HomeController::index()` validates filters through `IndexDashboardRequest` and
returns `Dashboard/Index` with a single typed `dashboard` snapshot and permitted
`stores`. `DashboardService` builds the widgets in one short read transaction;
`DashboardResource` exposes that snapshot, mirrored by
`resources/js/Types/dashboard-types.ts`.

- The page requires authentication. Widgets independently require `sales.view`
  (sales, trend, products, collections and store performance), `stock_alert.view`
  (inventory attention), or `shift.view` (cash-register shifts). Product links
  additionally require `inventory.view`.
- `PermissionsEnum::DASHBOARD_PROFIT_VIEW` is explicitly
  `dashboard.profit.view`, registered under the dashboard category by
  `PermissionSeeder` and granted to the super-administrator role. Dashboard
  profitability requires both this permission and `sales.view`.
- `ReportingScopeService` permits all stores for super-administrators and assigned
  stores for other users, including archived stores for historical reporting.
  An explicitly selected unauthorized store returns 403. `sales.view_all` expands
  sales visibility only within those permitted stores; otherwise sales and
  collections are restricted to orders owned by the current user. Profit access
  never expands store or personal-sales scope.
- Viewing new historical batch/opening-inventory costs requires the profit
  permission and the batch's store scope. `BatchResource` and
  `StockAdjustmentResource` enforce this. Batch/allocation models hide raw
  `unit_cost` and exclude it from activity-log properties. Existing purchasing
  permissions and catalog purchase-price visibility retain their meaning.

### Historical cost flow

1. **Receipt → batch:** on reception completion, `InventoryCostService` allocates
   the purchase order's `sub_total - discount` across purchase lines by line total,
   then divides each share by purchase quantity × the receipt's captured
   conversion factor. Every received batch freezes this per-base-unit cost in
   `Batch.unit_cost` (`decimal(24,12)`), with `cost_source=purchase_receipt`.
   Partial receipts use the same purchase-line basis.
2. **Opening inventory:** an initial-stock adjustment can explicitly record an
   optional per-base-unit cost with `cost_source=opening_inventory`. Submitting a
   cost requires the profit permission; later quantity adjustments retain the
   selected batch's cost. An omitted opening cost stays unknown.
3. **Batch → transfer:** transfer completion creates destination batches per
   source allocation, copying `unit_cost`, expiry and batch identifier, recording
   the immediate `source_batch_id` and `cost_source=transfer`. Repeated transfers
   preserve cost and lineage instead of using the destination's catalog price.
4. **Batch → sale snapshot:** fulfillment validates and locks the selected batches
   and freezes their actual base quantities and costs in
   `SalesOrderStockAllocation.quantity/unit_cost` (`decimal(24,12)` for cost).
   In the same transaction, `InventoryCostService::snapshotLineRevenue()` freezes
   each `SalesOrderItem.net_sales_amount` (`decimal(12,2)`) before recognizing the
   order. `FifoStockDeductionService` also copies batch cost when creating sale
   allocations. Subsequent catalog or batch-price changes do not reprice these
   sale snapshots.

`AmountAllocationService` uses decimal arithmetic and largest-remainder cent
allocation, with stable line-ID tie breaking. Sale weights are `line_total` after
line discounts; allocating `total - tax_amount` also distributes the order
discount exactly. Stored unit costs retain 12 decimal places; displayed monetary
totals round half-up to two.

Null cost is unknown; explicit zero is valid. A line is complete only when every
allocation has a known cost, allocated quantity equals quantity × conversion
factor, and frozen net revenue exists. Missing lines/snapshots make aggregate
gross profit, cost of goods and margin null, even on a fully discounted sale.
The UI shows **Incomplete costs**, missing-line count, line-based coverage and
profit for fully costed lines only. It never substitutes today's purchase price
or presents partial profit as total profit. Empty sales have no average sale;
zero net revenue has no margin.

### Reporting dates and current state

| Reading | Implemented basis |
| --- | --- |
| Net sales, orders, average sale and gross profit | `fulfilled_at` on fulfilled/completed orders; revenue is `total - tax_amount`, after discounts. Historical cost is the sum of frozen allocation quantity × unit cost. |
| Collections | Payment `created_at`, grouped by payment method and the order's store/owner scope. Includes tax, prepayments and later credit collections regardless of fulfillment date; receiving money does not recognize another sale. |
| Inventory attention | Current saleable stock per historically stocked variant/store, including depleted positions. Current active, non-archived stores participate; surplus at another store cannot hide a stockout. Expired stock is not saleable. |
| Open shifts | Current open shifts, independent of reporting dates. Expected cash is opening balance + cash payments + cash-in − cash-out. |
| Closed-shift discrepancies | `closed_at` within the report interval, including forced closes. Shortages and overages are summed separately, never netted. |

`ReportingPeriodService` resolves business-timezone boundaries from the timezone
setting, then converts them to the application storage timezone for half-open
`[start, end)` queries. Today ends at the generated snapshot time and compares
with the same weekday/time seven days earlier. Seven- and thirty-day presets
include today and shift their comparison by seven/thirty days; month-to-date uses
the matching elapsed part of the previous month, capped at its end. Custom ranges
include the selected end date, cap at now and compare with the preceding matching
calendar span; validation rejects future end dates and date differences over
365 days. Comparison can be disabled. Trend buckets are hourly for one business
day, daily through 90 days, then weekly; absent sales buckets are zero-filled.

Stock alerts count variant/store positions; expiry alerts count batches.
`expiry_alert_days` controls the upcoming-expiry window. A zero window disables
upcoming warnings while retaining expired-batch attention. The product ranking
contains up to eight variants by frozen net sales; combined-store performance
is paginated ten stores at a time. Selecting a store name applies that store to
the entire dashboard while preserving the reporting filters.

### Drill-down contract

All navigation uses Ziggy named routes and rechecks authorization server-side.

| Dashboard action | Destination and filters |
| --- | --- |
| View sales | `sales-orders`: applied `store_id`, `date_basis=fulfilled`, `from`, `to`, and `report_to=dashboard.period.end`. The exclusive cutoff reconciles the list with the captured trading period; sales scope remains personal unless `sales.view_all` permits more. |
| View alerts / stock-risk row | `inventory.alerts`: `per_store=1`, `tab=low-stock`, and the selected or row-specific store. Opens that store's alert list, not an individual product filter. |
| Expiry-risk row | `inventory.alerts`: `per_store=1`, `tab=expiry`, and the row's store. Current-state expiry rules match dashboard attention. |
| Top-selling product | `inventory.variants.show`: variant ID and applied `store_id`; opens the current authorized stock breakdown, independent of report dates. |
| Open shift / review closed shifts | `shifts.show` for the chosen shift; `shifts` for the list with `store_id`, `date_basis=closed`, `date_from`, `date_to`, and the same exclusive `report_to` cutoff. |

### Presentation and refresh

`Index.vue` composes `DashboardFilters`, `DashboardSummary`, `SalesTrendChart`,
`InventoryAttentionPanel`, `ProductPerformanceTable`, `CashShiftPanel` and
`StorePerformanceTable`. `useDashboard.ts` polls every 300,000 ms while visible,
stops while hidden, and refreshes immediately on return when the snapshot is at
least five minutes old. Refresh replaces `dashboard`, `stores` and `auth` together;
overlapping requests are guarded and applying filters cancels the previous one.
Failed refreshes retain the last successful applied results with a stale warning
and Retry action. Detected authentication/access loss hides the widgets; successful
refreshes recalculate capabilities and remove revoked profitability data.

- Filters use VeeValidate/Yup with explicit Apply, labelled fields and server
  validation errors. English/Spanish strings live in `resources/lang/en.json`
  and `resources/lang/es.json`; dates and currency use the existing formatters.
- Wide screens pair trend/inventory and products/cash detail; smaller screens
  stack sections. Below the `sm` breakpoint, product and store tables become
  labelled lists. The summary reflows into two columns and then one.
- The inherited Lato baseline is 16px. Observed dashboard type is 30px for the
  page title, 24px for summary readings, 20px for section titles and 14px for
  supporting labels, with tabular numeric values. Flat sections use 8px corners,
  1px borders and 20px padding/gaps; these describe this surface, not new global
  token definitions.
- Light/dark surfaces use the runtime Aura `surface-*` semantic tokens through
  the existing Tailwind bridge and `.app-dark` trigger. Operational Navy and its
  lighter ramp steps carry actions/chart emphasis. The incumbent One Instrument,
  One-Family, Status-Pairs and Flat-By-Default rules still apply.
- Semantic headings, definition lists, table captions/header scopes, named icon
  actions, busy/live/error announcements and labelled statuses support keyboard
  and assistive use. The chart disables animation, distinguishes comparison with
  a dashed line and legend, and provides an expandable data table beside its
  labelled canvas. These are implemented affordances, not a claim of a full
  accessibility conformance audit.

**Pre-existing system drift:** `DESIGN.md` records slate neutral values, including
dark surfaces, while the incumbent runtime Aura surfaces resolve to zinc. The
dashboard follows those existing runtime semantic tokens. This extension preserves
`PRODUCT.md`, `DESIGN.md` and `.impeccable/design.json`; the mismatch is recorded
here without redefining the global palette or canonizing the drift as a new rule.

## Development setup

Schema development edits the existing create-table migrations for batches,
sales-order stock allocations and sales-order items to add the historical fields
above, plus reporting indexes in the existing order/payment/shift migrations.
The explicitly approved disposable development database was rebuilt with:

```bash
php artisan migrate:fresh --seed --no-interaction
```

The normal seed registers permissions and settings. Additional roles need an
explicit profit grant and the appropriate store assignments. Visual review used
the opt-in fixture command:

```bash
php artisan db:seed --class=DashboardDemoSeeder --no-interaction
```

`DashboardDemoSeeder` remains outside `DatabaseSeeder`. Its Demo-labelled stores,
products and synthetic sales, costs, collections and shifts are demonstration
fixtures, not production trading evidence. The isolated MySQL verification
database was removed after verification; the dedicated browser session was closed
and the seeded administrator's locale/theme preferences restored to their defaults.

## Verification record

Recorded on 2026-11-09 from the implementation/independent finish handoff, inspected
source, saved test/build/lint outputs and existing captures. No tests or browser
commands were rerun for this documentation-only pass.

- **Cost/reporting coverage:** 18 new tests across
  `tests/Unit/AmountAllocationTest.php`,
  `tests/Feature/Inventory/InventoryCostTest.php` and
  `tests/Feature/Dashboard/DashboardTest.php` were exercised on SQLite and isolated
  MySQL 8.4. They cover cent allocation, discounted purchase packs/partial receipts,
  repeated transfers, multi-batch fulfillment, unknown/zero costs, scoped cost
  serialization, personal/store scope, permission revocation, collections timing,
  reporting boundaries including repeated DST hours, stock positions and shifts.
- **Affected workflows:** the initial 195-test regression run found two alert
  failures: strict page-size validation displaced existing clamping, and a random
  expiry fixture contaminated pagination totals. Both were fixed and targeted
  rechecks completed. The combined new-tests-plus-two-regressions recheck ran
  **20 tests / 162 assertions without failures**. After business-day/archived-store
  alignment, the latest reporting/alert recheck ran **13 tests / 146 assertions
  without failures**. This does not claim a clean rerun of all 195 tests.
- **Deprecations:** test output reports the existing PHP 8.5
  `PDO::MYSQL_ATTR_SSL_CA` deprecation from `config/database.php`; the deprecated
  test labels in those rechecks are not assertion failures.

| Check | Recorded result |
| --- | --- |
| `npm run type-check`, `npm run build` | Pass; build retains the existing large-chunk warnings. |
| ESLint on changed frontend files | Pass. |
| `composer validate`, `git diff --check` | Pass. |
| `composer lint` | Pint completes successfully; the full check is blocked by repository-wide PHPStan sort-direction typing and Pest 4 fixture typing errors, including conventional `$this` fixtures. Not a passing global check. |
| `npm run lint` | Blocked by legacy Options API, reserved component names and unused imports outside the dashboard. Not a passing global check. |

**Independent finish disposition: ship; no material fixes.** All six full-page
captures at 1440/768/390px in light/dark are coherent, with no document-width
overflow. Existing ignored artifacts are `.impeccable/review/desktop.png`,
`tablet.png`, `mobile.png` and their `-dark` variants; additional `english.png`,
`empty.png`, `incomplete.png`, `restricted.png` and `stale.png` record alternate
states. Missing-cost and restricted captures used browser response simulations;
real cost/permission tests separately back the behavior. The inspected mechanical
detector output `/tmp/opencode/dashboard-design-detect.json` is `[]`. No shipping
raster assets were introduced, so no new shipping-raster provenance is required.

Saved evidence includes `/tmp/opencode/dashboard-final-tests.log`,
`dashboard-period-final-tests.log`, `dashboard-mysql-tests.log`,
`dashboard-mysql-final-tests.log`, `dashboard-regression.log`,
`dashboard-deprecations.log`, `dashboard-final-lint.log` and
`dashboard-final-build.log`. These temporary logs and ignored screenshots are local
review evidence, not versioned deliverables. Full-project lint remains unresolved
as described above; no commits were requested or created for this handoff.
