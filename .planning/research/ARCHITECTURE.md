# Architecture Research

**Domain:** Sales management — POS, dashboard, and reports surfaces integrated into an existing Laravel 12 + Inertia.js + Vue 3 + PrimeVue module-pattern application
**Researched:** 2026-06-21
**Confidence:** HIGH

## Standard Architecture

### System Overview

```
┌──────────────────────────────────────────────────────────────────────────┐
│                           Browser Client                                  │
│  Vue 3 + Inertia.js v2 client + PrimeVue 4 + Tailwind 3 + TypeScript      │
├───────────────┬───────────────┬───────────────┬──────────────────────────┤
│  Admin Pages  │  POS Module   │  Dashboard    │  Reports Module          │
│  (existing)   │  (new, full)  │  (new, full)  │  (new, read-only)        │
│  AppLayout     │  PosLayout    │  AppLayout     │  AppLayout               │
│  Inertia      │  Pinia store  │  Inertia props │  Inertia props +         │
│  props +      │  + Inertia    │  (lazy eval) + │  useReportClient        │
│  composables  │  partial      │  router.reload │  (CSV export)            │
│               │  reloads      │  for polling   │                          │
└───────┬───────┴───────┬───────┴───────┬───────┴───────────┬──────────────┘
        │               │               │                    │
        ▼               ▼               ▼                    ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                    Web Routing Layer (routes/web.php)                     │
│  auth middleware → Inertia::render() + redirect()                        │
└───────┬───────┬───────────────┬───────────────┬──────────────────────────┘
        ▼       ▼               ▼               ▼
┌──────────┐ ┌──────────────┐ ┌────────────┐ ┌──────────────┐
│Existing  │ │PosController │ │HomeCtrl →  │ │ReportCtrl    │
│Module    │ │(Web, expand) │ │Dashboard   │ │(Web, new)    │
│Ctrls     │ │              │ │Service     │ │              │
└────┬─────┘ └──────┬───────┘ └─────┬──────┘ └──────┬───────┘
     │              │               │               │
     ▼              ▼               ▼               ▼
┌─────────────────────────────────────────────────────────────┐
│                        Service Layer                         │
│  SalesOrderService   CashRegisterShiftService   StockAlertSvc │
│  (reuse, fix tax)    (reuse, add TRANSITION_MAP) (reuse)      │
│  FifoStockDeductionService ──── (consolidate w/ BatchService) │
│  CashRegisterService ─────── (reuse)                         │
│  ─────────────────────────────────────────────────────────── │
│  NEW: DashboardService   (aggregates from existing services) │
│  NEW: ReportService      (read-only aggregations + exports)  │
└─────────────────────────────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────────────────────────────┐
│  MySQL Database (existing schema) + spatie/permission +     │
│  spatie/activitylog + spatie/medialibrary                    │
└─────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Implementation in this app |
|-----------|----------------|---------------------------|
| `PosController` (Web) | Authorize POS access, render POS page, delegate cart checkout to service | Expand existing `app/Http/Controllers/Pos/PosController.php` — currently only renders `Pos/Index` |
| `PosService` (new) | Orchestrate a POS sale: validate shift is open, build sales order payload, call `SalesOrderService::create()` with status `paid`, record cash movement if cash payment | `app/Services/PosService.php` (new) — thin orchestrator over existing services |
| `HomeController` (expand) | Render dashboard with KPI props (lazy-evaluated) | `app/Http/Controllers/HomeController.php` — inject `DashboardService` |
| `DashboardService` (new) | Aggregate today's sales, transaction count, cash on hand, low-stock counts, sales trend (7/30d), top products | `app/Services/DashboardService.php` (new) — reads from `SalesOrderService::list()` + `CashRegisterShiftService` + `StockAlertService` |
| `ReportController` (new, Web) | Authorize report view, render report pages, handle CSV/PDF export via streamed response | `app/Http/Controllers/ReportController.php` (new) |
| `ReportService` (new) | Aggregate by dimension (period, user, store, customer, category), compute profit margins, stock valuation; read-only | `app/Services/ReportService.php` (new) — direct query aggregation, no writes |
| `SalesOrderService` (reuse + fix) | Create/update sales orders, FIFO deduction, tax calculation | Existing — fix tax frontend/backend drift, add `SORT_COLUMN_MAP` if needed |
| `CashRegisterShiftService` (reuse + fix) | Open/close shifts, movements, expected closing | Existing — add `TRANSITION_MAP` + `validateTransition()` |
| `usePosStore` (expand) | Cart state, line items, discounts, payment input | Existing Pinia store — add cart slice |
| `usePosClient` (expand) | API calls for product search, customer search, session management | Existing composable — add `searchProducts`, `checkout` |
| `useReportClient` (new) | Trigger CSV/PDF export download | `resources/js/Composables/useReportClient.ts` (new) |

## Recommended Project Structure

### POS Module (new files + expansions)

```
app/
├── Http/
│   ├── Controllers/Pos/
│   │   └── PosController.php          # expand: add store(), hold(), resume()
│   ├── Requests/Pos/
│   │   ├── StoreSaleRequest.php       # new: validates cart payload → sales order
│   │   └── HoldSaleRequest.php        # new: validates hold order payload
│   └── Resources/Pos/
│       └── PosSaleResource.php        # new: receipt-shaped response
├── Services/
│   └── PosService.php                 # new: orchestrates checkout
resources/js/
├── Pages/Pos/
│   ├── Index.vue                      # expand: replace stub with full POS UI
│   └── Components/
│       ├── ProductSearch.vue           # new: barcode + search entry
│       ├── CartTable.vue               # new: line items, qty, per-line discount
│       ├── PaymentPanel.vue           # new: cash/card/mixed payment input
│       ├── OrderDiscountBar.vue       # new: order-level discount
│       ├── ShiftBar.vue               # (exists in PosLayout, reuse)
│       ├── ReceiptPreview.vue         # new: print receipt
│       ├── RegisterSelectDialog.vue   # exists
│       └── HoldRecallDialog.vue       # new: held orders list
├── Composables/
│   ├── usePosStore.ts                 # expand: add cart state + actions
│   └── usePosClient.ts                # expand: add searchProducts, checkout
└── Types/
    └── pos-types.ts                    # expand: CartItem, PaymentInput
```

### Dashboard Module (new files + expansions)

```
app/
├── Http/Controllers/
│   └── HomeController.php             # expand: inject DashboardService
├── Services/
│   └── DashboardService.php           # new: KPI aggregation
resources/js/
├── Pages/Dashboard/
│   ├── Index.vue                      # rewrite: replace stub with KPI cards + charts
│   └── Components/
│       ├── KpiCard.vue                 # new: reusable KPI card
│       ├── SalesTrendChart.vue         # new: line/area chart (PrimeVue Chart)
│       └── TopProductsChart.vue        # new: bar chart
└── Types/
    └── dashboard-types.ts              # new: KPI interfaces
```

### Reports Module (all new)

```
app/
├── Http/Controllers/
│   └── ReportController.php           # new: index, sales, inventory, cashRegister,
│                                      #   purchases, export endpoints
├── Requests/Reports/
│   ├── SalesReportRequest.php         # new: date range, store, user, customer filters
│   ├── InventoryReportRequest.php
│   └── CashRegisterReportRequest.php
├── Services/
│   └── ReportService.php              # new: aggregation queries + export generators
resources/js/
├── Pages/Reports/
│   ├── Index.vue                      # new: report selection landing
│   ├── Sales/Index.vue                # new: sales report with filters + table
│   ├── Inventory/Index.vue            # new: stock levels + valuation
│   ├── CashRegisters/Index.vue        # new: shifts + movements
│   └── Components/
│       ├── ReportFilters.vue          # new: shared date-range + dimension filters
│       └── ExportButtons.vue          # new: CSV/PDF download trigger
├── Composables/
│   └── useReportClient.ts             # new: export download trigger
└── Types/
    └── report-types.ts                # new: report row interfaces
```

### Structure Rationale

- **POS as a subdirectory controller (`Pos/PosController.php`):** The existing app already places POS in `app/Http/Controllers/Pos/`. Keep this — POS is a specialized surface, not a standard CRUD module. Its Form Requests live in `app/Http/Requests/Pos/` (mirroring `Auth/`), not `Requests/PosSales/`.
- **Dashboard reuses `HomeController`:** The route `home` → `HomeController::index` already renders `Dashboard/Index`. Expand `HomeController` to inject `DashboardService` rather than creating a new `DashboardController`. This avoids a new route and keeps the home route stable.
- **Reports as a standard Web controller:** `ReportController.php` at the controllers root (flat file, like `UserController.php`). Reports are read-only with no model to bind, so no `ReportController/` subdirectory needed. Form Requests validate filter input (date ranges, store IDs) but there's no Resource layer — reports return plain arrays via Inertia props, not Eloquent Resources.
- **`PosService` as orchestrator, not reimplementation:** POS checkout is fundamentally "create a paid sales order linked to an open shift." That logic already exists in `SalesOrderService::create()`. The new `PosService` wraps the orchestration (validate shift open → build payload → call `SalesOrderService::create()` with status `paid` → add cash movement if payment is cash) but does not duplicate the totals calculation or FIFO deduction.

## Architectural Patterns

### Pattern 1: Inertia Lazy Props + Partial Reloads for Dashboard/Reports Data

**What:** Use Inertia's `fn ()` lazy evaluation and `Inertia::lazy()` for dashboard/report props so heavy aggregation queries only run when the page is actually visited or when a partial reload explicitly requests them. Use `router.reload({ only: ['kpis'] })` for polling/refresh.

**When to use:** Dashboard KPIs, chart data, report tables — any data that is expensive to compute and doesn't need to be in the initial page load of every page.

**Trade-offs:**
- Pros: Server-side aggregation runs only when needed; partial reloads refresh just the changed prop without re-rendering the whole page; no separate API endpoint needed.
- Cons: All data flows through Inertia props (not a separate JSON API), which is fine for this app since the API layer is being removed.

**Example:**
```php
// HomeController.php (expanded)
public function index(): InertiaResponse
{
    $this->authorize(PermissionsEnum::DASHBOARD_VIEW);
    $actor = $request->user();

    return Inertia::render('Dashboard/Index', [
        'alertSummary' => fn () => $this->stockAlertService->getSummary(),
        'kpis' => Inertia::lazy(fn () => $this->dashboardService->getKpis($actor)),
        'salesTrend' => Inertia::lazy(fn () => $this->dashboardService->getSalesTrend(days: 7)),
        'topProducts' => Inertia::lazy(fn () => $this->dashboardService->getTopProducts(limit: 10)),
    ]);
}
```

```typescript
// Dashboard/Index.vue — polling refresh
router.reload({ only: ['kpis', 'salesTrend', 'topProducts'], preserveScroll: true });
```

### Pattern 2: Pinia Store for Cart State + Inertia Partial Reloads for Product Catalog

**What:** The POS cart lives entirely in Pinia `usePosStore` (client-side state). The product catalog (search results) is fetched via the existing `api.v1.variants.search` API endpoint (one of the ~10 kept endpoints) using `usePosClient.searchProducts()`. The cart is never sent to the server until checkout — the server only receives the final `StoreSaleRequest` payload.

**When to use:** POS cart management — ephemeral client state that must survive page navigation within the POS module but doesn't need server persistence until checkout.

**Trade-offs:**
- Pros: Cart survives Inertia navigations (Pinia is app-level state); no server round-trips for cart edits; product search uses the existing API endpoint pattern.
- Cons: Cart is lost on full page refresh (acceptable for POS — the shift session check re-initializes state); if the browser crashes mid-sale, the cart is gone (mitigated by the "hold order" feature which persists to the DB as status `held`).

**Example:**
```typescript
// usePosStore.ts (expanded)
export const usePosStore = defineStore("pos", () => {
  // existing: store, register, shift, userId
  const cart = ref<CartItem[]>([]);
  const orderDiscountType = ref<DiscountType>('flat');
  const orderDiscountValue = ref(0);
  const payments = ref<PaymentInput[]>([]);

  const subTotal = computed(() => cart.value.reduce((sum, item) => sum + item.lineTotal, 0));
  const discount = computed(() => /* match SalesOrderService::calculateTotals formula */);
  const taxAmount = computed(() => /* (subTotal - discount) * (taxRate / 100) */);
  const total = computed(() => subTotal.value - discount.value + taxAmount.value);

  function addToCart(variant: ProductVariantSearchResult, quantity: number) { /* ... */ }
  function removeFromCart(variantId: number) { /* ... */ }
  function updateQuantity(variantId: number, quantity: number) { /* ... */ }
  function clearCart() { cart.value = []; payments.value = []; }
  // ...
});
```

### Pattern 3: Server-Side Streamed Export for CSV/PDF

**What:** Reports export via Laravel's `response()->streamDownload()` — the controller calls `ReportService` to generate CSV rows or PDF bytes and streams them to the browser. No client-side generation.

**When to use:** Report exports — server has the data and the aggregation logic; generating large CSVs client-side would require fetching all rows first.

**Trade-offs:**
- Pros: Server can stream arbitrarily large exports; no JSON round-trip for potentially thousands of rows; reuses the same `ReportService` methods used for the on-screen table.
- Cons: Export is a separate HTTP request (not an Inertia visit) — use a regular `<a href>` or `window.location` to trigger the download, not `router.visit()`.

**Example:**
```php
// ReportController.php
public function exportSales(SalesReportRequest $request): StreamedResponse
{
    $this->authorize(PermissionsEnum::REPORTS_VIEW);
    $filters = $request->validated();

    return response()->streamDownload(
        function () use ($filters): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['Order ID', 'Date', 'Customer', 'User', 'Store', 'Total', 'Status']);
            foreach ($this->reportService->salesRows($filters) as $row) {
                fputcsv($out, [$row->id, $row->created_at, $row->customer_name, ...]);
            }
            fclose($out);
        },
        "sales-report-{$filters['from']}-to-{$filters['to']}.csv",
        ['Content-Type' => 'text/csv'],
    );
}
```

```typescript
// useReportClient.ts
export function useReportClient() {
  function exportSalesCsv(filters: ReportFilters): void {
    // Streamed download — not an Inertia visit
    const params = new URLSearchParams(filters as Record<string, string>);
    window.location.href = route("reports.sales.export", params.toString());
  }
  return { exportSalesCsv };
}
```

### Pattern 4: PosService as Thin Orchestrator (No Business Logic Duplication)

**What:** `PosService` does NOT recompute totals, deduct stock, or manage shifts. It validates the shift is open, constructs the payload shape `SalesOrderService::create()` expects, calls it with `status: 'paid'`, and optionally adds a cash movement.

**When to use:** Whenever a new surface reuses existing service capabilities — the new service is a coordinator, not a reimplementation.

**Trade-offs:**
- Pros: Tax calculation, FIFO deduction, and activity logging live in one place (`SalesOrderService`); the existing `SalesOrderService::create()` already handles `status === 'paid'` → `fifoStockDeductionService->deductForOrder()`.
- Cons: `SalesOrderService::create()` currently doesn't record a cash movement for cash payments — `PosService` must do that after the order is created.

**Example:**
```php
// PosService.php
final class PosService
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
        private readonly CashRegisterShiftService $shiftService,
    ) {}

    public function checkout(array $cartData, User $cashier): SalesOrder
    {
        $shift = CashRegisterShift::findOrFail($cartData['cash_register_shift_id']);
        if ($shift->status !== CashRegisterShiftStatus::OPEN) {
            throw new InvalidArgumentException('Cannot checkout on a closed shift.');
        }

        $order = $this->salesOrderService->create(
            data: [
                'customer_id' => $cartData['customer_id'] ?? null,
                'cash_register_shift_id' => $shift->id,
                'status' => SalesOrderStatus::PAID->value,
                'discount_type' => $cartData['discount_type'],
                'discount_value' => $cartData['discount_value'],
                'items' => $cartData['items'],
                'payments' => $cartData['payments'],
                'notes' => $cartData['notes'] ?? null,
            ],
            actor: $cashier,
        );

        // Record cash movement for cash payments
        $cashPaid = collect($cartData['payments'])
            ->where('payment_method', PaymentMethod::CASH->value)
            ->sum('amount');
        if ($cashPaid > 0) {
            $this->shiftService->addMovement(
                $shift,
                CashMovementType::CASH_IN->value,
                (float) $cashPaid,
                "Sale #{$order->id}",
                $cashier,
            );
        }

        return $order;
    }
}
```

### Pattern 5: Receipt Printing — Client-Side Print Window

**What:** Receipt printing is client-side: the `PosSaleResource` returns the receipt-shaped data (order, items, payments, shift, totals), and the Vue component opens a print-friendly window via `window.print()` on a dedicated receipt layout. No server-generated PDF.

**When to use:** POS receipts — thermal printers respond to browser print commands; no PDF generation overhead; receipt format matches the on-screen preview.

**Trade-offs:**
- Pros: Instant printing; no PDF library dependency; receipt layout matches the screen preview; supports any printer the browser can access.
- Cons: Receipt formatting depends on browser print CSS (mitigated with a dedicated `@media print` stylesheet); no archived PDF copy (the sales order itself is the archived record).

## Data Flow

### POS Sale → SalesOrder Flow

```
1. Cashier scans/searches product → usePosClient.searchProducts()
   → GET /api/v1/variants/search → VariantsController::search
   → returns ProductVariantCollection (kept API endpoint)

2. Cashier adds to cart → usePosStore.addToCart(variant, qty)
   → cart state in Pinia (client-only, no server call)

3. Cashier applies discounts, enters payments → usePosStore (client-only)
   → taxRate read from useAuth().getSetting('sales', 'tax_rate', 0)
   → frontend computes: (subTotal - discount) * (taxRate / 100) [MUST match backend]

4. Cashier clicks "Checkout" → router.post(route('pos.sale.store'), cartPayload)
   → routes/web.php → PosController::store(StoreSaleRequest)
   → StoreSaleRequest validates cart payload + authorizes POS_ACCESS
   → PosController delegates to PosService::checkout()
   → PosService validates shift is open
   → PosService calls SalesOrderService::create(data, actor) with status='paid'
     → SalesOrderService::calculateTotals() [server-side source of truth]
     → SalesOrder::create() + SalesOrderItem::create() + SalesOrderPayment::create()
     → status === 'paid' → FifoStockDeductionService::deductForOrder()
     → activity('sales_order')->log()
   → PosService calls CashRegisterShiftService::addMovement() for cash payments
   → PosController returns redirect to receipt or Inertia render with order

5. Receipt → PosSaleResource → ReceiptPreview.vue → window.print()
```

### Dashboard KPI Flow

```
1. Manager navigates to /home → HomeController::index()
   → DashboardService::getKpis(actor)
     → today's sales: SalesOrder::whereDate('created_at', today())
       ->where('store_id', actor->store)->sum('total')
     → transaction count: SalesOrder::whereDate(...)->count()
     → cash on hand: CashRegisterShift::where('status', 'open')->sum('opening_balance')
       + movements sum (cash_in - cash_out) + cash sales
     → low-stock count: StockAlertService::getSummary()['low_stock_count']
   → DashboardService::getSalesTrend(days: 7)
     → SalesOrder::selectRaw('DATE(created_at) as date, SUM(total) as total')
       ->where('created_at', '>=', now()->subDays(7))
       ->groupBy('date')->orderBy('date')->get()
   → DashboardService::getTopProducts(limit: 10)
     → SalesOrderItem::select('product_variant_id')
       ->selectRaw('SUM(quantity) as qty, SUM(line_total) as revenue')
       ->join('sales_orders', ...) // where paid, last 30 days
       ->groupBy('product_variant_id')->orderByDesc('revenue')->limit(10)
       ->with('productVariant.product')->get()

2. Inertia renders Dashboard/Index with lazy props (only evaluated on visit)

3. Vue page renders KPI cards (PrimeVue) + charts (PrimeVue Chart component)
   → router.reload({ only: ['kpis'] }) for manual refresh or polling
```

### Report Aggregation Flow

```
1. Manager navigates to /reports/sales → ReportController::sales(SalesReportRequest)
   → ReportService::salesReport(filters)
     → SalesOrder::query()
       ->with(['customer', 'user', 'store'])
       ->whereBetween('created_at', [$from, $to])
       ->when($storeId, fn($q) => $q->where('store_id', $storeId))
       ->when($userId, fn($q) => $q->where('user_id', $userId))
       ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
       ->orderBy('created_at', 'desc')->paginate($perPage)
   → ReportService::salesSummary(filters) // totals, avg, count

2. Inertia renders Reports/Sales/Index with report data as props

3. Manager clicks "Export CSV" → window.location.href = route('reports.sales.export', filters)
   → ReportController::exportSales(SalesReportRequest)
   → response()->streamDownload() → ReportService::salesRows($filters) generator
   → browser downloads sales-report-2026-01-01-to-2026-06-21.csv
```

### State Management

```
[Pinia usePosStore] — cart, discounts, payments (client-only, ephemeral)
    ↓ (subscribe via storeToRefs)
[POS Vue components] ←→ [usePosClient actions] → [API: variants.search, pos.session*]
    ↓ (checkout)
[Inertia router.post] → [PosController] → [PosService] → [SalesOrderService] → [DB]

[Dashboard Vue page] ← [Inertia props (lazy)] ← [HomeController] ← [DashboardService]
    ↓ (polling)
[router.reload({ only: ['kpis'] })] → [HomeController] → [DashboardService]

[Reports Vue page] ← [Inertia props] ← [ReportController] ← [ReportService] → [DB]
    ↓ (export)
[window.location.href] → [ReportController::export*] → [streamDownload] → [CSV file]
```

### Key Data Flows

1. **POS cart → SalesOrder:** Cart state in Pinia is serialized into the `StoreSaleRequest` payload shape (items[], payments[], discount_type, discount_value, customer_id, cash_register_shift_id). `PosService` passes this to `SalesOrderService::create()` with `status: 'paid'`. The server recomputes totals (never trusts client-computed totals) and deducts stock via `FifoStockDeductionService`.
2. **Dashboard KPIs:** All KPIs are computed server-side by `DashboardService` querying `SalesOrder`, `CashRegisterShift`, and `StockAlertService` directly. No client-side aggregation.
3. **Report aggregation:** `ReportService` queries `SalesOrder`, `ProductVariant` (stock), `CashRegisterShift`+`CashRegisterMovement`, and `PurchaseOrder`/`ReceptionOrder` directly with aggregate queries (SUM, COUNT, GROUP BY). Results are plain arrays/objects, not Eloquent Resources.
4. **Export:** `ReportController` streams CSV via `response()->streamDownload()`. The same `ReportService` method that powers the on-screen table (`salesRows()`) is reused as a generator for the CSV stream.

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 1-50 users (current) | Monolith with Inertia lazy props + partial reloads is optimal. Dashboard aggregation queries run on each visit — fine at this scale. |
| 50-500 users | Cache `DashboardService::getKpis()` with `Cache::remember('dashboard.kpis', 300)` keyed by store. Report aggregation queries should use `->toBase()` to avoid model hydration. Consider queueing PDF exports (if added). |
| 500+ users | Move dashboard polling to a dedicated lightweight JSON endpoint (not Inertia). Cache report aggregations per filter-set. Consider read replicas for report queries. |

### Scaling Priorities

1. **First bottleneck (50+ users):** Dashboard KPI queries on every visit. Fix: cache KPIs for 5 minutes per store using `Cache::remember()` with key-based invalidation (not `Cache::tags()` — see CONCERNS.md).
2. **Second bottleneck (200+ users):** Report aggregation queries on large datasets. Fix: add composite indexes on `sales_orders.created_at`, `sales_orders.store_id`, `sales_order_payments.payment_method`; consider materialized summary tables if report latency exceeds 2s.

## Anti-Patterns

### Anti-Pattern 1: Creating a Separate PosSaleService that Duplicates Sales Order Logic

**What people do:** Build a `PosSaleService` that recomputes totals, deducts stock, and creates sales order records independently of `SalesOrderService`.
**Why it's wrong:** The tax calculation formula, FIFO deduction, and activity logging would drift between the two paths. The `CONCERNS.md` already flags frontend/backend tax drift as a bug — duplicating the backend logic guarantees more drift.
**Do this instead:** `PosService` is a thin orchestrator that calls `SalesOrderService::create()` with `status: 'paid'`. The only POS-specific logic is shift validation and cash movement recording.

### Anti-Pattern 2: Client-Side Report Data Aggregation

**What people do:** Fetch all sales orders via an API endpoint and aggregate in the Vue component.
**Why it's wrong:** Transferring thousands of rows to the client for aggregation is slow, bandwidth-heavy, and bypasses database indexes. The existing app is removing its API layer, not adding new heavy endpoints.
**Do this instead:** `ReportService` runs aggregate SQL queries (SUM, COUNT, GROUP BY) server-side. The Vue page receives pre-aggregated arrays/objects via Inertia props.

### Anti-Pattern 3: Adding New API Endpoints for Dashboard/Report Data Fetching

**What people do:** Create `api.v1.dashboard.kpis`, `api.v1.reports.sales` JSON endpoints and call them via axios composables.
**Why it's wrong:** The project decision (PROJECT.md) is to remove the unused API layer, not expand it. New dynamic fetches should prefer Inertia partial-reload / deferred props over new API endpoints. Adding API endpoints increases the convention-alignment workload and the attack surface.
**Do this instead:** Use Inertia lazy props (`Inertia::lazy(fn () => ...)`) + `router.reload({ only: [...] })` for dashboard polling and report data. Only use API endpoints for truly client-side dynamic fetches (product search, customer search) that already exist.

### Anti-Pattern 4: Trusting Client-Computed Totals in POS Checkout

**What people do:** Send `total`, `tax_amount`, `sub_total` from the Pinia cart and persist them directly.
**Why it's wrong:** The client can be manipulated; the server must be the source of truth for financial data. `SalesOrderService::calculateTotals()` already exists and is the authoritative computation.
**Do this instead:** Send only `items[]`, `payments[]`, `discount_type`, `discount_value` from the cart. `SalesOrderService::create()` calls `calculateTotals()` server-side and persists those values. The client-computed totals are only for display preview.

### Anti-Pattern 5: Generating Receipts as Server-Side PDFs

**What people do:** Install a PDF library (dompdf, snappy) and generate receipt PDFs on the server.
**Why it's wrong:** Adds a heavy dependency for a feature that thermal printers handle natively via browser print. PDF generation is slow, and the receipt layout already exists as a Vue component.
**Do this instead:** Return receipt data via `PosSaleResource` and print via `window.print()` with a `@media print` receipt stylesheet. If archived PDF receipts are needed later, that's a separate feature.

## Integration Points

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| POS ↔ SalesOrderService | Direct service call via `PosService` | `PosService::checkout()` calls `SalesOrderService::create()` — no new API endpoint |
| POS ↔ CashRegisterShiftService | Direct service call via `PosService` | `PosService::checkout()` calls `addMovement()` for cash payments; shift open/close via existing `CashRegisterShiftController` web routes |
| Dashboard ↔ SalesOrderService | `DashboardService` queries `SalesOrder` model directly | Does NOT call `SalesOrderService::list()` (that returns paginated models with relations; dashboard needs raw aggregates) — queries `SalesOrder` directly with `sum()`, `count()`, `selectRaw()` |
| Dashboard ↔ StockAlertService | `DashboardService` calls `StockAlertService::getSummary()` | Reuse existing method, already cached via `HandleInertiaRequests` |
| Dashboard ↔ CashRegisterShiftService | `DashboardService` queries `CashRegisterShift` model directly | Cash-on-hand is a raw sum query, not a paginated list |
| Reports ↔ SalesOrderService | `ReportService` queries `SalesOrder` + `SalesOrderItem` directly | Read-only aggregate queries; does NOT call `SalesOrderService::list()` (different shape) |
| Reports ↔ StockService/Inventory | `ReportService` queries `ProductVariant` + `Batch` directly | Stock valuation = `SUM(remaining_quantity * unit_cost)` across batches |
| Reports ↔ CashRegisterShiftService | `ReportService` queries `CashRegisterShift` + `CashRegisterMovement` directly | Shift/movement history aggregation |
| Reports ↔ PurchaseOrderService | `ReportService` queries `PurchaseOrder` + `ReceptionOrder` directly | Purchases & receptions report |

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Browser print API | `window.print()` + `@media print` CSS | Receipt printing — no server-side PDF generation |
| PrimeVue Chart component | Direct import from `primevue/chart` | Dashboard sales trend + top products charts |

## API Layer Removal: Shared Resources and Safe Deletion Order

### Used API Endpoints (KEEP)

These endpoints are called by Vue pages/composables. They must remain in `routes/api.php` and their controllers/resources must be kept:

| API Endpoint | Called By (composable/page) | Controller | Resources Used |
|-------------|---------------------------|------------|----------------|
| `api.v1.variants.search` | `useSalesOrderClient` (SOLineItemsTable), `useVariantClient` (variant search), `StockAdjustments/Create` (inline axios), `StockTransfers/Create` (inline axios), `Vendors/Catalog` (inline axios) | `Api/VariantsController` | `ProductVariantCollection` |
| `api.v1.variants.purchase-units` | `useVariantClient` (Inventory Show), `Vendors/Catalog` (inline axios) | `Api/VariantsController` | — (returns plain array) |
| `api.v1.variants.purchase-price-history` | `useVariantClient` (PurchasePriceMargin) | `Api/VariantsController` | — |
| `api.v1.variants.vendors` | `usePurchaseOrderClient` (POVariantVendorsDialog) | `Api/VariantsController` | `VariantVendorResource` |
| `api.v1.variants` (index) | `StockAdjustments/Create`, `StockTransfers/Create`, `Vendors/Catalog` (inline axios) | `Api/VariantsController` | `ProductVariantCollection` |
| `api.v1.vendors.variants` | `usePurchaseOrderClient` (POLineItemsTable) | `Api/VendorsController` | `VendorCatalogCollection` |
| `api.v1.customers.search` | `useCustomerClient` (CustomerSelect) | `Api/CustomerController` | — |
| `api.v1.customers.find-by-tax-id` | `useCustomerClient` (CustomerSelect) | `Api/CustomerController` | — |
| `api.v1.customers.store` | `useCustomerClient` (CustomerSelect) | `Api/CustomerController` | — |
| `api.v1.batches.available` | `useBatchClient` (StockAdjustments/Create) | `Api/BatchesController` | `BatchResource` |
| `api.v1.activity-logs` | `useActivityLogClient` (ActivityLogs/Index) | `Api/ActivityLogController` | `ActivityLogResource` |
| `api.v1.settings` | `useSettingClient` (Settings page) | `Api/SettingsController` | — |
| `api.v1.settings.update` | `useSettingClient` | `Api/SettingsController` | — |
| `api.v1.permissions` | `usePermissionClient` (Roles create/edit) | `Api/PermissionsController` | — |
| `api.v1.pos.*` | `usePosClient` (Pos/Index, RegisterSelectDialog) | **NOTE: These routes don't exist yet in api.php** — `usePosClient` references `api.v1.pos.session`, `api.v1.pos.registers`, `api.v1.pos.session.register`, `api.v1.pos.session.shift.open`, `api.v1.pos.session.shift.close` but these routes are NOT in `routes/api.php`. This is a gap — the POS session API must be implemented or the POS client should use web routes instead. |

**Critical gap:** The `usePosClient` composable calls 5 API routes (`api.v1.pos.session`, `api.v1.pos.registers`, `api.v1.pos.session.register`, `api.v1.pos.session.shift.open`, `api.v1.pos.session.shift.close`) that do NOT exist in `routes/api.php`. The POS feature must either:
- (a) Add these API routes + a `PosSessionController` API controller, OR
- (b) Refactor `usePosClient` to use existing web routes (`shifts.open`, `shifts.close`, `cash-registers`) via Inertia `router` instead of axios.

**Recommendation:** Option (b) — refactor `usePosClient` to use web routes via Inertia `router.post()`. This aligns with the project decision to remove the API layer. The POS session concept (store + register + shift selection) can be managed via the Pinia store and existing web routes for shift open/close. This avoids adding new API endpoints for a feature that should use the same web-based auth/session model as the rest of the app.

### Shared Resources (MUST NOT delete — used by Web controllers)

These Resources are imported by **both** Web controllers (Inertia) and API controllers. Deleting them breaks the web module:

| Resource | Used by Web Controller | Used by API Controller |
|----------|----------------------|----------------------|
| `SalesOrder\SalesOrderResource` | `SalesOrderController` (show, edit via `->resolve()`) | — (no API sales order endpoint exists) |
| `SalesOrder\SalesOrderCollection` | `SalesOrderController::index` | — |
| `SalesOrderItem\SalesOrderItemResource` | `SalesOrderResource` (nested) | — |
| `SalesOrderPayment\SalesOrderPaymentResource` | `SalesOrderResource` (nested) | — |
| `CashRegisterShift\CashRegisterShiftResource` | `CashRegisterShiftController` | — (needs fix: `relationLoaded()` bug) |
| `CashRegisterShift\CashRegisterShiftCollection` | `CashRegisterShiftController` | — |
| `CashRegisterMovement\CashRegisterMovementResource` | `CashRegisterShiftResource` (nested) | — |
| `CashRegister\CashRegisterResource` | `CashRegisterController` | — |
| `CashRegister\CashRegisterCollection` | `CashRegisterController` | — |
| `Category\CategoryCollection` | `CategoryController` | `Api/CategoryController` |
| `Brand\BrandCollection` | `BrandController` | `Api/BrandController` |
| `MeasurementUnit\MeasurementUnitCollection` | `MeasurementUnitController` | `Api/MeasurementUnitController` |
| `Role\RoleResource` | `RoleController` | — |
| `Role\RoleCollection` | `RoleController` | `Api/RoleController` (via `ApiCollection`) |
| `User\UserResource` | — | `Api/UserController` |
| `User\UserCollection` | `UserController` | `Api/UserController` |
| `Product\ProductCollection` | `ProductController` | — |
| `Product\ProductVariantResource` | `CatalogController` | `Api/VariantsController` |
| `Product\ProductVariantCollection` | — | `Api/VariantsController` |
| `Catalog\CatalogResource` | `CatalogController` | — |
| `Catalog\CatalogCollection` | `CatalogController` | — |
| `Catalog\CatalogVariantResource` | `CatalogController` | — |
| `Catalog\CatalogVariantCollection` | `CatalogController` | — |
| `Catalog\VariantVendorResource` | — | `Api/VariantsController` |
| `PurchaseOrder\PurchaseOrderResource` | — (web uses Collection only) | — (API returns raw models — bug) |
| `PurchaseOrder\PurchaseOrderCollection` | `PurchaseOrdersController` | — |
| `ReceptionOrder\ReceptionOrderResource` | — (web uses Collection) | — |
| `ReceptionOrder\ReceptionOrderCollection` | `ReceptionOrderController` | — |
| `StockTransfer\StockTransferResource` | `StockTransferController` (show) | — (needs fix: `toISOString()` bug) |
| `StockTransfer\StockTransferCollection` | `StockTransferController` | — |
| `StockTransfer\StockTransferItemResource` | `StockTransferResource` (nested) | — |
| `StockAdjustment\StockAdjustmentResource` | `StockAdjustmentController` | — |
| `StockAdjustment\StockAdjustmentCollection` | `StockAdjustmentController` | — |
| `Inventory\StockOverviewCollection` | `InventoryController` | — |
| `Batches\BatchResource` | `BatchController` | `Api/BatchesController` |
| `Batches\BatchCollection` | `BatchController` | — |
| `Store\StoreResource` | — | — |
| `Store\StoreCollection` | `StoreController` | — |
| `Vendor\VendorCollection` | `VendorsController` | — |
| `Vendor\VendorCatalogCollection` | `CatalogController` (vendorIndex) | `Api/VendorsController` |
| `Vendor\VendorCatalogResource` | `CatalogController` (vendorEdit) | — |
| `Customer\CustomerCollection` | `CustomerController` | — |
| `ActivityLog\ActivityLogResource` | — | `Api/ActivityLogController` (KEEP — used by ActivityLogs page) |
| `ApiCollection` | — | Used by 8 API controllers (KEEP if any kept API controller uses it) |

### API Controllers Safe to Delete (NOT used by any Vue page/composable)

| API Controller | Routes to Delete | Resources Used (check if shared) |
|---------------|-----------------|-------------------------------|
| `Api/CategoryController` | `api.v1.categories.*` (6 routes) | Uses `ApiCollection` (shared) — don't delete `ApiCollection` until all API controllers using it are gone |
| `Api/BrandController` | `api.v1.brands.*` (6 routes) | Uses `ApiCollection` |
| `Api/MeasurementUnitController` | `api.v1.measurement-units.*` (6 routes) | Uses `ApiCollection` |
| `Api/RoleController` | `api.v1.roles.*` (5 routes) | Uses `ApiCollection` |
| `Api/UserController` | `api.v1.users.*` (9 routes) | Uses `User\UserResource`, `User\UserCollection` (shared with web UserController — keep resources) |
| `Api/VendorsController` | `api.v1.vendors.*` (8 routes) | Uses `ApiCollection`, `Vendor\VendorCatalogCollection` (shared with web CatalogController — keep resource). **DELETE the controller, but fix mass-assignment first or delete before adding `$fillable` fields.** |
| `Api/PurchaseOrdersController` | `api.v1.purchase-orders.*` (5 routes) | Uses `ApiCollection`. **Has mass-assignment bug + raw model returns — deleting eliminates the security issue entirely.** |
| `Api/SettingsController` | `api.v1.settings`, `api.v1.settings.update` | Uses `ApiCollection`. **No `authorize()` calls — deleting eliminates the authorization gap.** |
| `Api/PermissionsController` | `api.v1.permissions` | Uses `ApiCollection`. **No `authorize()` — deleting eliminates the gap.** |

### API Controllers to KEEP (used by composables)

| API Controller | Why Kept | Resources Used |
|---------------|---------|---------------|
| `Api/VariantsController` | Used by `useSalesOrderClient`, `useVariantClient`, `usePurchaseOrderClient`, inline axios in 3 pages | `ProductVariantCollection`, `VariantVendorResource` |
| `Api/CustomerController` | Used by `useCustomerClient` (CustomerSelect in SalesOrders) | — (plain arrays) |
| `Api/BatchesController` | Used by `useBatchClient` (StockAdjustments/Create) | `BatchResource` |
| `Api/ActivityLogController` | Used by `useActivityLogClient` (ActivityLogs/Index) | `ActivityLogResource` |
| `Api/VendorsController` (only `getProductVariants` method) | Used by `usePurchaseOrderClient` (POLineItemsTable → `api.v1.vendors.variants`) | `VendorCatalogCollection` |

**Note on `Api/VendorsController`:** Only `getProductVariants` (route `api.v1.vendors.variants`) is used. The CRUD methods (`index`, `show`, `store`, `update`, `destroy`, `storeProductVariant`, `updateProductVariants`, `removeProductVariant`) are NOT used by any composable. Safe approach: keep the controller file but remove unused methods, or move `getProductVariants` into `Api/VariantsController` and delete `Api/VendorsController` entirely.

### API Form Requests Safe to Delete

| API Form Request Dir | Status |
|---------------------|--------|
| `Api/Categories/` | DELETE — no longer needed after `Api/CategoryController` deletion |
| `Api/Brands/` | DELETE — no longer needed |
| `Api/MeasurementUnits/` | DELETE |
| `Api/Roles/` | DELETE |
| `Api/Users/` | DELETE — web Form Requests remain |
| `Api/Customers/` | KEEP — `Api/CustomerController::store` may use it |
| `Api/ActivityLogs/` | Check if `Api/ActivityLogController` uses it — likely keep |

### Safe Deletion Order

1. **First:** Fix the mass-assignment bugs in `Api/VendorsController` and `Api/PurchaseOrdersController` — OR delete these controllers entirely (which eliminates the bugs without fixing them). **Deleting is safer** since the endpoints aren't used by any composable.
2. **Second:** Delete unused API controllers: `Api/CategoryController`, `Api/BrandController`, `Api/MeasurementUnitController`, `Api/RoleController`, `Api/UserController`, `Api/SettingsController`, `Api/PermissionsController`, `Api/PurchaseOrdersController`.
3. **Third:** Trim `Api/VendorsController` to only `getProductVariants()` (or move that method to `Api/VariantsController` and delete the file).
4. **Fourth:** Delete unused API routes from `routes/api.php` — keep only: `variants.*`, `customers.search`, `customers.find-by-tax-id`, `customers.store`, `batches.available`, `activity-logs`, `settings`, `settings.update`, `permissions`, `vendors.variants`.
5. **Fifth:** Delete unused API Form Request directories (`Api/Categories/`, `Api/Brands/`, etc.).
6. **Sixth:** Check if `ApiCollection` is still used by any kept controller. If not, delete it. If `Api/CustomerController`, `Api/ActivityLogController`, `Api/BatchesController`, `Api/VariantsController` don't use `ApiCollection`, it can go.
7. **Seventh:** Delete composables that only called deleted endpoints: `useBrandClient`, `useCategoryClient`, `useMeasurementUnitClient`, `useRoleClient`, `useUserClient`, `useProductClient`, `useVendorClient` (check each — some may have methods used by pages). **Verify by grepping Pages for each composable import before deleting.**
8. **Eighth:** Fix `useApi.ts` X-XSRF-TOKEN bug (CONCERNS.md) — this affects all remaining API calls.

**Important:** Do NOT delete these Resources even if their API controller is deleted — they are used by Web controllers:
- `Category\CategoryCollection` (used by web `CategoryController`)
- `Brand\BrandCollection` (used by web `BrandController`)
- `MeasurementUnit\MeasurementUnitCollection` (used by web `MeasurementUnitController`)
- `Role\RoleCollection`, `Role\RoleResource` (used by web `RoleController`)
- `User\UserCollection` (used by web `UserController`)
- `Vendor\VendorCollection` (used by web `VendorsController`)
- `Vendor\VendorCatalogCollection` (used by web `CatalogController`)

## Build Order: Fixes vs Features

### Critical Principle: Fixes Before Features

The critical fixes in CONCERNS.md must happen BEFORE the new feature work, because the features depend on the correctness of the existing services.

### Dependency Graph: Fixes → Features

```
FIX: SalesOrderService tax (frontend/backend drift)
  └─→ POS feature (POS computes tax in cart preview, must match backend)
  └─→ Dashboard "today's sales" KPI (sums total column, must be correct)
  └─→ Reports sales report (aggregates total, tax_amount)

FIX: CashRegisterShiftService TRANSITION_MAP
  └─→ POS feature (shift open/close/force-close is core POS flow)

FIX: CashRegisterShiftResource relationLoaded() bug
  └─→ POS feature (POS reads shift data via Inertia)
  └─→ Dashboard cash-on-hand (if reading shift details)
  └─→ Reports cash register report

FIX: SalesOrderResource N+1 (eager load items)
  └─→ POS receipt (needs items for receipt)
  └─→ Reports (eager loading prevents slow queries)

FIX: Cache::tags() removal in Setting
  └─→ ALL features (Setting::get('tax_rate') is called by SalesOrderService)

FIX: FIFO consolidation (BatchService vs FifoStockDeductionService)
  └─→ POS checkout (calls SalesOrderService → FifoStockDeductionService)

FIX: orderBy whitelist (SORT_COLUMN_MAP)
  └─→ Reports (report filters pass orderBy from user input)

FIX: Mass-assignment in Api/VendorsController, Api/PurchaseOrdersController
  └─→ API layer removal (delete the controllers = eliminates the bug)

FIX: Missing authorize() in 5 API controllers
  └─→ API layer removal (delete the controllers = eliminates the gap)

FIX: useApi.ts X-XSRF-TOKEN bug
  └─→ All remaining API calls (POS product search, customer search, etc.)
```

### Recommended Build Order (Phase Sequence)

| Phase | Focus | Rationale |
|-------|-------|----------|
| 1 | **Critical backend fixes** (tax, Cache::tags, FIFO consolidation, TRANSITION_MAP, resource bugs, authorize gaps, orderBy whitelists) | These are blockers for feature correctness. Tax drift makes POS unusable; Cache::tags crashes on default driver; resource bugs drop data. |
| 2 | **API layer removal** (delete unused controllers/routes/requests/composables, trim VendorsController) | Removes attack surface, eliminates mass-assignment + missing-authorize bugs by deletion, reduces convention-alignment workload. Must happen before new features to avoid building on dead code. |
| 3 | **Convention alignment** (casts() method, LogsActivity trait, catch narrowing, AuthServiceProvider, TS errors, useApi.ts fix) | Brings codebase to a consistent state before adding new modules. |
| 4 | **POS module** (full: product search, cart, payments, shift integration, receipt, hold/recall) | Depends on fixed SalesOrderService (tax), CashRegisterShiftService (TRANSITION_MAP), and trimmed API layer (usePosClient refactored to web routes). |
| 5 | **Dashboard module** (KPIs, charts, activity feed) | Depends on fixed SalesOrderService (correct totals for KPIs) and POS (sales created in step 4 feed the dashboard). |
| 6 | **Reports module** (sales, inventory, cash register, purchases, exports) | Depends on fixed services + orderBy whitelists. Read-only, no new writes. |
| 7 | **Test coverage** (SalesOrderService, FifoStockDeductionService, CashRegisterShiftService, PosService, DashboardService, ReportService) | Validates the financial core after all fixes and features are in place. |

### Which Fixes Block Which Features

| Fix | Blocks Feature | Why |
|-----|---------------|-----|
| Tax frontend/backend drift | POS, Dashboard, Reports | POS cart preview must match backend; dashboard KPIs sum the `total` column (which includes tax); reports aggregate taxed totals |
| `Cache::tags()` in Setting | ALL features | `Setting::get('tax_rate')` is called by `SalesOrderService::create()` — crashes on `file` driver |
| `CashRegisterShiftService` TRANSITION_MAP | POS | POS shift open/close/force-close is core flow; without centralized transitions, the logic is scattered and fragile |
| `CashRegisterShiftResource` `relationLoaded()` bug | POS, Reports | POS reads shift data; reports show shift/movement history; movements are silently dropped |
| `SalesOrderResource` N+1 (items) | POS receipt, Reports | Receipt needs items; report detail views need items; N+1 causes slow loads |
| FIFO consolidation | POS checkout | POS checkout deducts stock via `FifoStockDeductionService`; if `BatchService` has different behavior, stock transfers and sales could diverge |
| `orderBy` whitelist | Reports | Report filters pass user-selected sort columns; without whitelists, this is an injection risk |
| `useApi.ts` X-XSRF-TOKEN | POS product search, all API composables | Broken CSRF header on all API calls; if axios stops auto-attaching, all API calls fail |

## New Permissions Required

| Permission | Enum Case | Used By |
|-----------|-----------|---------|
| Dashboard view | `DASHBOARD_VIEW = 'dashboard.view'` | `HomeController::index` |
| Reports view | `REPORTS_VIEW = 'reports.view'` | `ReportController::*` |
| Reports export | `REPORTS_EXPORT = 'reports.export'` | `ReportController::export*` |
| POS sale (checkout) | `POS_SALE = 'pos.sale'` (or reuse `POS_ACCESS`) | `PosController::store` |

These must be added to `PermissionsEnum`, registered in `PermissionSeeder`, and seeded via `php artisan db:seed --class=PermissionSeeder`.

## Sources

- `.planning/codebase/ARCHITECTURE.md` — existing module pattern, data flow, component responsibilities (HIGH confidence — codebase analysis)
- `.planning/codebase/STRUCTURE.md` — directory layout, naming conventions, where to add new code (HIGH confidence — codebase analysis)
- `.planning/codebase/CONCERNS.md` — critical issues, security gaps, known bugs (HIGH confidence — codebase analysis)
- `routes/web.php` — existing web routes (HIGH confidence — source code)
- `routes/api.php` — existing API routes (HIGH confidence — source code)
- `app/Services/SalesOrderService.php` — sales order creation, tax calculation, FIFO deduction (HIGH confidence — source code)
- `app/Services/CashRegisterShiftService.php` — shift management, movements, expected closing (HIGH confidence — source code)
- `app/Services/StockAlertService.php` — stock alert summary used by dashboard (HIGH confidence — source code)
- `app/Http/Controllers/HomeController.php` — current dashboard controller (HIGH confidence — source code)
- `app/Http/Controllers/Pos/PosController.php` — current POS controller stub (HIGH confidence — source code)
- `resources/js/Composables/usePosStore.ts` — existing Pinia store (HIGH confidence — source code)
- `resources/js/Composables/usePosClient.ts` — existing POS API client (HIGH confidence — source code)
- `app/Enums/PermissionsEnum.php` — existing permissions (HIGH confidence — source code)
- Inertia.js v1 documentation — partial reloads, lazy data evaluation, `router.reload({ only: [...] })` (HIGH confidence — official docs, version-matched)
- Laravel 12 documentation — `response()->streamDownload()` for CSV export (HIGH confidence — official docs, version-matched)
- Composable/page grep analysis — which API endpoints are actually called by Vue pages (HIGH confidence — grep over source code)

---
*Architecture research for: POS, Dashboard, and Reports integration into existing Laravel + Inertia sales-management app*
*Researched: 2026-06-21*