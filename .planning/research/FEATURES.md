# Feature Research

**Domain:** Retail sales management — POS interface, manager dashboard, standard sales report suite
**Researched:** 2026-06-21
**Confidence:** HIGH (grounded in existing data model + retail industry standards)

## Scope Note

This research covers ONLY the three new surfaces for an existing sales-management app: **POS interface**, **manager dashboard**, and **reports**. Products, inventory (batches/FIFO/transfers/adjustments/alerts), purchase orders, reception orders, sales orders (payments/tax/discounts/transitions), customers, vendors, cash registers (shifts/movements), stores, users/roles/permissions, activity log, settings, and media already exist and are NOT re-researched.

Existing data model relevant to this research (verified from `app/Models/`):
- `SalesOrder`: `sub_total`, `discount`, `discount_type`, `discount_value`, `tax_amount`, `total`, `status`, `store_id`, `user_id`, `customer_id`, `cash_register_shift_id`, `token`, relations → `items`, `payments`, `customer`, `user`, `store`, `cashRegisterShift`
- `SalesOrderItem`: `quantity`, `unit_price`, `conversion_factor`, `line_total`, `product_variant_id`, `sale_unit_id` — **NO per-line discount column, NO cost column**
- `SalesOrderPayment`: payment rows per order (supports mixed/split tender + partial payment)
- `CashRegisterShift`: `opening_balance`, `closing_balance`, `expected_closing`, `difference`, `opened_at`, `closed_at`, `status`, relations → `movements`, `salesOrders`
- `CashRegisterMovement`: `type` (cash_in/cash_out), `amount`, `reason`
- `Batch`: FIFO stock with `quantity`, `unit_cost`, `expiry_date` (supports inventory valuation + expiry tracking)
- `ProductVariant`: has `sku` (referenced in resource) + relations to `product`, sale units

---

## Feature Landscape

### Table Stakes (Users Expect These)

#### POS Interface — Table Stakes

| # | Feature | Why Expected | Complexity | Dependencies | New Permissions |
|---|---------|--------------|------------|--------------|-----------------|
| POS-1 | Register/shift selection landing | Cashier must pick an open register + shift before selling; existing `usePosStore` + `RegisterSelectDialog` already scaffold this | S | `CashRegisterService`, `CashRegisterShiftService` (existing) | `POS_ACCESS` (existing) |
| POS-2 | Shift open / close / force-close with opening & closing balance | Every retail POS requires shift lifecycle; cash reconciliation at close is the Z-report moment | M | `CashRegisterShiftService` (existing, needs `TRANSITION_MAP` fix from CONCERNS) | `SHIFTS_OPEN`, `SHIFTS_CLOSE`, `SHIFTS_MANAGE` (existing) |
| POS-3 | Cash movements (cash in / cash out) mid-shift | Cashiers add/remove float, pay petty expenses; tracked against shift difference | S | `CashRegisterMovement` model (existing), `CASH_MOVEMENTS_CREATE` (existing) | existing |
| POS-4 | Product search (by name/SKU/barcode) + add to cart | Core POS action; product/variant search endpoint already exists | M | `ProductService` search (existing), `useProductClient` | existing |
| POS-5 | Barcode/scan entry into cart | Hardware scanner acts as keyboard; an input that captures Enter-key = add line is table stakes | S | POS-4 | existing |
| POS-6 | Cart management: line items, qty +/-, remove line | Minimum viable cart | M | `SalesOrderItem` model (existing), `usePosStore` | existing |
| POS-7 | Per-line discount (amount or %) | Retail cashiers apply line discounts constantly; **NOTE: `SalesOrderItem` has NO discount column — needs migration or computed approach** | M | Schema gap: add `discount_type`, `discount_value`, `discount` to `sales_order_items` (new migration OK — genuinely new column) | existing `SALES_CREATE` |
| POS-8 | Order-level discount (amount or %) | Order-level promo or manager discount; `SalesOrder` already has `discount_type/value/discount` | S | Existing columns | existing |
| POS-9 | Tax computed from `sales.tax_rate` setting | Tax must match backend `/100` formula; CONCERNS notes frontend currently hardcodes `taxRate = 0` — must fix | S | `Setting::get('sales.tax_rate')`, `useAuth().getSetting()` | existing |
| POS-10 | Payment collection: cash, card, mixed (split tender) | `SalesOrderPayment` supports multiple payment rows → mixed payment is a table-stakes capability the schema already enables | M | `SalesOrderPayment` (existing), `PaymentMethod` enum | existing |
| POS-11 | Payment-difference / change calculation | Cash tender vs total → change due; validation against taxed total | S | POS-10 | existing |
| POS-12 | Partial payment / pay-later on order | Customer pays part now, rest later; requires order status `partially_paid`; status enum may need a case | M | `SalesOrderStatus` enum, `SalesOrderService` | existing |
| POS-13 | Customer attach to sale | Loyalty/history tracking + returns require customer; `customer_id` exists on `SalesOrder`; walk-in = nullable | S | `CustomerService` search (existing), `useCustomerClient` | existing |
| POS-14 | Hold / park order + recall | Cashier suspends a sale to serve another customer; parked orders resume with cart intact | M | `SalesOrderStatus::HOLD` or a `held` state + `usePosStore` held-orders list | existing |
| POS-15 | Print receipt | Thermal/web receipt printer; browser print of a styled receipt template is table stakes | M | Receipt template (Vue/Blade print view), browser `window.print()` or print-to-USB | existing |
| POS-16 | Checkout completes sale → creates `SalesOrder` (status `completed`) + deducts FIFO stock + links shift | End-to-end sale is the core value flow; `SalesOrderService::create()` + `FifoStockDeductionService` already exist | L | `SalesOrderService`, `FifoStockDeductionService` (existing), must be wired to POS submit | existing |
| POS-17 | Returns / refunds | Retail POS must process returns: reverse stock, refund payment; existing `SalesOrder` status enum + FIFO deduction can be inverted | L | New `SalesOrderReturnType` or refund flow on existing order; stock reversal via `BatchService` | **`POS_RETURNS` (NEW)** |
| POS-18 | Quick keys / favorites grid (product shortcuts) | Speed of service; a configurable grid of top SKUs is standard | M | `usePosStore`, optional user-specific quick-key settings | existing |
| POS-19 | On-screen numeric keypad for cash entry | Touch-only environments without physical keypad | S | UI only | existing |

#### Manager Dashboard — Table Stakes

| # | Feature | Why Expected | Complexity | Dependencies | New Permissions |
|---|---------|--------------|------------|--------------|-----------------|
| DASH-1 | KPI card: Today's sales total | The single most-asked manager question; `SUM(total) WHERE status=completed AND date=today` | S | `SalesOrder` aggregate query | **`DASHBOARD_VIEW` (NEW)** |
| DASH-2 | KPI card: Today's transaction count | Volume signal | S | `SalesOrder` count | existing |
| DASH-3 | KPI card: Average basket value (total / count) | Basket-size health | S | DASH-1 + DASH-2 | existing |
| DASH-4 | KPI card: Low-stock alert count | Existing `StockAlertService::getSummary()` already shared via `HandleInertiaRequests` | S | `StockAlertService` (existing) | existing |
| DASH-5 | KPI card: Cash on hand (open shifts) | Manager wants float visibility; `SUM(closing_balance OR current) WHERE shift open` | S | `CashRegisterShift` aggregate | existing |
| DASH-6 | Sales trend chart (last 7 / 30 days) | Line/area chart of daily sales totals; PrimeVue Chart component | M | Daily aggregate query, `Chart` from primevue | existing |
| DASH-7 | Top products chart (today or 7d) | Bar chart of top N products by revenue or qty | M | `SalesOrderItem` aggregate join | existing |
| DASH-8 | Recent activity feed | Already have `spatie/activitylog` + `ActivityLogController`; surface last N entries | S | `activity()` log read (existing) | `ACTIVITY_LOGS_VIEW` (existing) |
| DASH-9 | Time range selector (Today / 7d / 30d / Custom) | Manager toggles KPI/chart window | M | Date-range param to dashboard controller | existing |
| DASH-10 | Store filter (for multi-store managers) | Filter KPIs by store | S | `store_id` filter on aggregates | existing |

#### Reports — Table Stakes

| # | Feature | Why Expected | Complexity | Dependencies | New Permissions |
|---|---------|--------------|------------|--------------|-----------------|
| REP-1 | Sales report by period | Daily/weekly/monthly sales totals + breakdown | M | `SalesOrder` aggregate by date | **`REPORTS_VIEW` (NEW, base)**, **`REPORTS_SALES` (NEW)** |
| REP-2 | Sales report by user (cashier) | Manager compares cashier performance | M | `SalesOrder` GROUP BY `user_id` | `REPORTS_SALES` |
| REP-3 | Sales report by store | Multi-store comparison | M | `SalesOrder` GROUP BY `store_id` | `REPORTS_SALES` |
| REP-4 | Sales report by customer | Top-customer analysis | M | `SalesOrder` GROUP BY `customer_id` | `REPORTS_SALES` |
| REP-5 | Inventory levels report (stock on hand per variant) | What's in stock now; `Batch` quantities grouped by variant | M | `Batch` aggregate, `StockService` | **`REPORTS_INVENTORY` (NEW)** |
| REP-6 | Inventory valuation report (cost × qty) | Accounting needs stock value; `SUM(batch.quantity * batch.unit_cost)` | M | `Batch.unit_cost` (existing) | `REPORTS_INVENTORY` |
| REP-7 | Expiry tracking report | Batches with `expiry_date` near/over; existing stock alerts cover some | M | `Batch.expiry_date` (existing) | `REPORTS_INVENTORY` |
| REP-8 | Cash register / Z-report (shifts + movements) | End-of-day cash reconciliation per shift; `CashRegisterShift` + movements | M | `CashRegisterShift`, `CashRegisterMovement` (existing) | **`REPORTS_CASH` (NEW)** |
| REP-9 | Purchases & receptions report | What was ordered vs received; `PurchaseOrder` + `ReceptionOrder` | M | `PurchaseOrder`, `ReceptionOrder` (existing) | **`REPORTS_PURCHASES` (NEW)** |
| REP-10 | Top products report (by revenue & qty) | Best-seller list over a period | M | `SalesOrderItem` aggregate | `REPORTS_SALES` |
| REP-11 | Profit margin report (revenue − cost) | `line_total − (qty * variant_cost)`; **NOTE: `SalesOrderItem` has NO cost column — must use `ProductVariant` cost or `Batch.unit_cost` via FIFO** | L | Cost lookup via `ProductVariant` or `Batch`; needs service method | `REPORTS_SALES` |
| REP-12 | Tax summary report | Tax collected per period (for filings); `SUM(tax_amount)` grouped | S | `SalesOrder.tax_amount` (existing) | `REPORTS_SALES` |
| REP-13 | Returns / refunds report | Returns volume + value; depends on POS-17 returns existing | M | Returns data (from POS-17) | `REPORTS_SALES` |
| REP-14 | Stock movement log (transfers + adjustments + sales deductions) | Audit of every stock change; `StockTransfer`, `StockAdjustment`, FIFO deductions | M | Existing services | `REPORTS_INVENTORY` |
| REP-15 | Report filters: date range, store, user, category, customer | Every report needs consistent filter dimensions | M | Shared filter component + query builder | all `REPORTS_*` |
| REP-16 | Export: CSV | Universal export; smallest effort via streamed response | S | Laravel streaming response | all `REPORTS_*` |
| REP-17 | Export: PDF | Printable report; requires a PDF library | M | New dep: `barryvdh/laravel-dompdf` or `niklacrp/dompdf` | all `REPORTS_*` |
| REP-18 | Export: Excel (xlsx) | Finance teams expect spreadsheet export | M | New dep: `maatwebsite/excel` or `spatie/simple-excel` | all `REPORTS_*` |

### Differentiators (Competitive Advantage)

| # | Feature | Value Proposition | Complexity | Dependencies | New Permissions |
|---|---------|-------------------|------------|--------------|-----------------|
| DIFF-1 | Offline POS mode (PWA / service worker) | Sell when network drops; sync when reconnected | XL | PWA service worker, local queue, conflict resolution | existing |
| DIFF-2 | Loyalty program (points per purchase) | Repeat-customer retention | L | New `LoyaltyPoint` model, rules engine, customer balance | **`LOYALTY_MANAGE` (NEW)** — defer |
| DIFF-3 | Gift cards | Prepaid balance as payment method | L | New `GiftCard` model, balance, redemption in payment flow | **`GIFT_CARDS_MANAGE` (NEW)** — defer |
| DIFF-4 | ABC analysis (product classification by revenue contribution) | Inventory strategy insight (A = top 20% revenue, C = bottom) | M | `SalesOrderItem` aggregate + classification | `REPORTS_INVENTORY` |
| DIFF-5 | Slow-mover detection (low-velocity products) | Identify dead stock to discount/clear | M | Sales velocity per variant over window | `REPORTS_INVENTORY` |
| DIFF-6 | Predictive reorder suggestions | "You'll run out of X in 3 days based on velocity" | L | Sales velocity + current stock + lead time | `REPORTS_INVENTORY` |
| DIFF-7 | Dashboard live refresh (polling / WebSocket) | Manager sees real-time sales without manual reload | M | Inertia polling or WebSocket server | existing |
| DIFF-8 | Per-cashier sales comparison chart on dashboard | Spot top/bottom performers at a glance | M | `SalesOrder` GROUP BY user + date | existing |
| DIFF-9 | Printed receipt customization (logo, footer message, tax breakdown) | Branding + compliance | S | Receipt template config from settings | existing |
| DIFF-10 | Email/SMS receipt | Customer prefers digital receipt | M | Mail/SMS integration, customer contact | existing |
| DIFF-11 | Dashboard export (PDF snapshot of current view) | Manager shares today's snapshot with owner | S | PDF lib (shared with REP-17) | `DASHBOARD_VIEW` |

### Anti-Features (Commonly Requested, Often Problematic)

| # | Feature | Why Requested | Why Problematic | Alternative |
|---|---------|---------------|-----------------|-------------|
| ANTI-1 | Table service / table layout map | "Restaurant mode" request | Hospitality domain, not retail; table state, course firing, kitchen tickets are a different product | Stay retail-focused; if needed later, separate app |
| ANTI-2 | Kitchen / order ticket printing | Restaurant ticket routing | Same — hospitality feature | Out of scope |
| ANTI-3 | Payroll / staff scheduling | "Manage my employees too" | HR is a different domain with legal complexity (overtime, contracts); dilutes sales focus | Export hours via cashier sales report; integrate with HR tool |
| ANTI-4 | Full accounting / general ledger | "Replace my accounting software" | Accounting requires double-entry, COA, journals, fiscal compliance — massive scope | Export sales/tax/purchase reports for import into accounting tool |
| ANTI-5 | CRM / marketing campaigns (email blasts) | "Email my customers" | Marketing automation is its own product category; spam compliance, templates, analytics | Export customer list; integrate with Mailchimp etc. |
| ANTI-6 | E-commerce storefront sync | "Sync stock with my online shop" | Each platform (Shopify/WooCommerce) has its own integration; high maintenance | Provide CSV import/export + future integration milestone |
| ANTI-7 | Native mobile POS app (iOS/Android) | "I want a phone app" | PROJECT.md explicitly puts responsive POS as future milestone; native = separate codebase | Web-first PWA (DIFF-1) covers offline mobile |
| ANTI-8 | Real-time everything (live stock, live dashboard via WS everywhere) | "Make it all real-time" | WebSocket infra + complexity for low-frequency data | Polling on dashboard (DIFF-7) is enough; stock refreshes on action |
| ANTI-9 | Custom report builder (drag-and-drop) | "Let users build any report" | Huge UX + query-builder scope; most users want the standard 12 reports | Ship the standard suite (REP-1..14); add saved views later |
| ANTI-10 | Multi-currency / FX conversion | "Sell in USD and BOB" | Requires exchange-rate feed, gain/loss accounting; current app is single-currency (BOB settings) | Single currency via settings; defer FX to accounting integration |
| ANTI-11 | Loyalty + gift cards in v1 (DIFF-2/3) | "Need loyalty to compete" | Both are L-complexity new modules; v1 must ship the core sale flow first | Ship table-stakes POS, add loyalty/gift cards as v1.x if validated |

---

## Feature Dependencies

```
[POS-1 Register/shift selection]
    └──requires──> [POS-2 Shift open]  (must open before selling)
                        └──enables──> [POS-3 Cash movements]
                                      [POS-16 Checkout]

[POS-4 Product search] ──enables──> [POS-5 Barcode scan]
                                    [POS-6 Cart mgmt]
                                        └──requires──> [POS-7 Per-line discount*]
                                                        [POS-8 Order-level discount]
                                                        [POS-9 Tax from settings]
                                        └──enables──> [POS-14 Hold/recall]
                                                        [POS-10 Payment (split tender)]
                                                            └──enables──> [POS-11 Change calc]
                                                                          [POS-12 Partial payment]
                                        └──enables──> [POS-13 Customer attach]
                                                        [POS-18 Quick keys]
[POS-10 Payment] + [POS-16 Checkout] ──requires──> [SalesOrderService.create + FifoStockDeductionService (existing)]
                                                └──enables──> [POS-15 Print receipt]
                                                              [POS-17 Returns/refunds]

[POS-17 Returns] ──requires──> [POS-16 Checkout] (must have completed sales to return)

[Dashboard DASH-1..7] ──requires──> [POS-16 Checkout producing completed SalesOrders]
[DASH-4 Low-stock] ──reuses──> [StockAlertService (existing)]
[DASH-8 Recent activity] ──reuses──> [activitylog (existing)]

[Reports REP-1..4,10,12,13] ──requires──> [POS-16 Checkout producing completed SalesOrders]
[REP-5,6,7,14] ──reuses──> [Batch, StockTransfer, StockAdjustment (existing)]
[REP-8 Cash report] ──reuses──> [CashRegisterShift, CashRegisterMovement (existing)]
[REP-9 Purchases report] ──reuses──> [PurchaseOrder, ReceptionOrder (existing)]
[REP-11 Profit margin] ──requires──> [Cost source: ProductVariant cost OR Batch.unit_cost (existing)] + new service method
[REP-13 Returns report] ──requires──> [POS-17 Returns]
[REP-15 Filters] ──enhances──> [all REP-*]
[REP-16 CSV export] ──enhances──> [all REP-*]
[REP-17 PDF export] ──requires──> [PDF library dependency]
[REP-18 Excel export] ──requires──> [Excel library dependency]

[DIFF-4 ABC analysis] ──enhances──> [REP-10 Top products]
[DIFF-5 Slow-mover] ──enhances──> [REP-5 Inventory levels]
[DIFF-6 Predictive reorder] ──enhances──> [DIFF-5] + [REP-5]
[DIFF-7 Dashboard live refresh] ──enhances──> [Dashboard]
```

### Dependency Notes

- **POS-7 Per-line discount requires a schema change:** `sales_order_items` currently has no `discount_type`/`discount_value`/`discount` columns. This is a genuinely new column → a new migration is justified (PROJECT.md allows new migrations for genuinely new tables/columns). Backend `SalesOrderItem` fillable + resource must be updated. Without this, per-line discounts cannot persist.
- **POS-9 Tax requires fixing the hardcoded `taxRate = 0`** noted in CONCERNS before the POS can compute tax correctly.
- **POS-17 Returns is the largest dependency for REP-13 Returns report** — if returns are deferred, the returns report is deferred too.
- **REP-11 Profit margin has no direct cost column on `SalesOrderItem`** — implementation must resolve cost via `ProductVariant` (current cost field) or the FIFO `Batch.unit_cost` at time of sale. The latter is more accurate but requires capturing cost at sale time (consider a `cost_amount` snapshot column on `sales_order_items` — new migration). This is a design decision to surface in phase planning.
- **POS-16 Checkout is the keystone feature** — dashboard and reports all depend on it producing completed `SalesOrder` rows. It must be built before dashboard/reports can show real data.
- **Dashboard and reports share aggregation logic** — both compute sales totals; factor into a shared `SalesAnalyticsService` to avoid duplication.
- **PDF (REP-17) and Excel (REP-18) exports introduce new composer dependencies** — decide early (phase planning) which libraries to add.

---

## MVP Definition

### Launch With (v1) — This Milestone

The milestone's Core Value (per PROJECT.md) is: *"A complete sale (POS → shift close → dashboard KPI → sales report) works end-to-end with no manual workaround."* v1 must deliver that whole loop.

- [ ] **POS-1** Register/shift selection — entry point
- [ ] **POS-2** Shift open/close/force-close — cash reconciliation
- [ ] **POS-3** Cash movements — float mgmt
- [ ] **POS-4** Product search — find products
- [ ] **POS-5** Barcode scan — speed
- [ ] **POS-6** Cart management — core cart
- [ ] **POS-7** Per-line discount — requires schema migration (new column)
- [ ] **POS-8** Order-level discount — uses existing columns
- [ ] **POS-9** Tax from settings — fix hardcoded 0
- [ ] **POS-10** Payment (cash/card/mixed) — split tender
- [ ] **POS-11** Change calculation
- [ ] **POS-13** Customer attach
- [ ] **POS-14** Hold/recall order
- [ ] **POS-15** Print receipt
- [ ] **POS-16** Checkout → SalesOrder + FIFO deduction — keystone
- [ ] **POS-17** Returns/refunds — retail table stakes
- [ ] **POS-18** Quick keys — speed
- [ ] **DASH-1..7** KPI cards + sales trend + top products
- [ ] **DASH-8** Recent activity feed
- [ ] **DASH-9** Time range selector
- [ ] **DASH-10** Store filter
- [ ] **REP-1..4** Sales by period/user/store/customer
- [ ] **REP-5,6,7** Inventory levels/valuation/expiry
- [ ] **REP-8** Cash register Z-report
- [ ] **REP-9** Purchases & receptions report
- [ ] **REP-10** Top products
- [ ] **REP-11** Profit margin
- [ ] **REP-12** Tax summary
- [ ] **REP-13** Returns report
- [ ] **REP-14** Stock movement log
- [ ] **REP-15** Report filters
- [ ] **REP-16** CSV export
- [ ] **REP-17** PDF export
- [ ] **REP-18** Excel export
- [ ] **Permissions:** all new `REPORTS_*`, `DASHBOARD_VIEW`, `POS_RETURNS` cases added to `PermissionsEnum` + `PermissionSeeder`

### Add After Validation (v1.x)

- [ ] **POS-12** Partial payment — trigger: customer request for layaway/pay-later
- [ ] **POS-19** On-screen keypad — trigger: touch-only deployment
- [ ] **DIFF-7** Dashboard live refresh — trigger: manager asks "why do I have to reload?"
- [ ] **DIFF-8** Cashier comparison chart — trigger: multi-cashier store
- [ ] **DIFF-9** Receipt customization — trigger: branding request
- [ ] **DIFF-10** Email/SMS receipt — trigger: customer asks for digital receipt
- [ ] **DIFF-11** Dashboard PDF snapshot — trigger: owner wants daily email

### Future Consideration (v2+)

- [ ] **DIFF-1** Offline PWA mode — large effort; defer until network reliability is a real pain
- [ ] **DIFF-2** Loyalty program — defer unless customer demand; new module
- [ ] **DIFF-3** Gift cards — defer unless customer demand; new module
- [ ] **DIFF-4** ABC analysis — defer; nice analytical add-on once base reports validated
- [ ] **DIFF-5** Slow-mover detection — defer; builds on REP-5
- [ ] **DIFF-6** Predictive reorder — defer; requires velocity history + lead-time data

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| POS-16 Checkout (keystone) | HIGH | HIGH | P1 |
| POS-6 Cart mgmt | HIGH | MEDIUM | P1 |
| POS-10 Payment (split tender) | HIGH | MEDIUM | P1 |
| POS-4 Product search | HIGH | MEDIUM | P1 |
| POS-2 Shift lifecycle | HIGH | MEDIUM | P1 |
| POS-15 Print receipt | HIGH | MEDIUM | P1 |
| POS-7 Per-line discount (needs migration) | HIGH | MEDIUM | P1 |
| POS-9 Tax from settings (fix hardcoded) | HIGH | LOW | P1 |
| POS-17 Returns/refunds | HIGH | HIGH | P1 |
| POS-14 Hold/recall | MEDIUM | MEDIUM | P1 |
| POS-13 Customer attach | MEDIUM | LOW | P1 |
| POS-18 Quick keys | MEDIUM | MEDIUM | P1 |
| POS-1 Register/shift selection | HIGH | LOW | P1 |
| POS-5 Barcode scan | HIGH | LOW | P1 |
| POS-8 Order-level discount | MEDIUM | LOW | P1 |
| POS-11 Change calc | HIGH | LOW | P1 |
| POS-3 Cash movements | MEDIUM | LOW | P1 |
| DASH-1 Today's sales | HIGH | LOW | P1 |
| DASH-2 Tx count | MEDIUM | LOW | P1 |
| DASH-3 Avg basket | MEDIUM | LOW | P1 |
| DASH-4 Low-stock alert | MEDIUM | LOW | P1 |
| DASH-5 Cash on hand | MEDIUM | LOW | P1 |
| DASH-6 Sales trend chart | HIGH | MEDIUM | P1 |
| DASH-7 Top products chart | HIGH | MEDIUM | P1 |
| DASH-8 Recent activity | LOW | LOW | P1 |
| DASH-9 Time range selector | MEDIUM | MEDIUM | P1 |
| DASH-10 Store filter | MEDIUM | LOW | P1 |
| REP-1 Sales by period | HIGH | MEDIUM | P1 |
| REP-2 Sales by user | HIGH | MEDIUM | P1 |
| REP-3 Sales by store | HIGH | MEDIUM | P1 |
| REP-5 Inventory levels | HIGH | MEDIUM | P1 |
| REP-6 Inventory valuation | HIGH | MEDIUM | P1 |
| REP-8 Cash Z-report | HIGH | MEDIUM | P1 |
| REP-10 Top products | HIGH | MEDIUM | P1 |
| REP-11 Profit margin | HIGH | HIGH | P1 |
| REP-12 Tax summary | HIGH | LOW | P1 |
| REP-15 Report filters | HIGH | MEDIUM | P1 |
| REP-16 CSV export | HIGH | LOW | P1 |
| REP-17 PDF export | MEDIUM | MEDIUM | P1 |
| REP-4 Sales by customer | MEDIUM | MEDIUM | P1 |
| REP-7 Expiry tracking | MEDIUM | MEDIUM | P1 |
| REP-9 Purchases report | MEDIUM | MEDIUM | P1 |
| REP-13 Returns report | MEDIUM | MEDIUM | P1 |
| REP-14 Stock movement log | MEDIUM | MEDIUM | P1 |
| REP-18 Excel export | MEDIUM | MEDIUM | P1 |
| POS-12 Partial payment | MEDIUM | MEDIUM | P2 |
| POS-19 Numeric keypad | LOW | LOW | P2 |
| DIFF-7 Dashboard live refresh | MEDIUM | MEDIUM | P2 |
| DIFF-8 Cashier comparison | MEDIUM | MEDIUM | P2 |
| DIFF-9 Receipt customization | LOW | LOW | P2 |
| DIFF-10 Email/SMS receipt | MEDIUM | MEDIUM | P2 |
| DIFF-11 Dashboard PDF snapshot | LOW | LOW | P2 |
| DIFF-4 ABC analysis | MEDIUM | MEDIUM | P3 |
| DIFF-5 Slow-mover detection | MEDIUM | MEDIUM | P3 |
| DIFF-6 Predictive reorder | HIGH | HIGH | P3 |
| DIFF-1 Offline PWA | HIGH | XL | P3 |
| DIFF-2 Loyalty | MEDIUM | HIGH | P3 |
| DIFF-3 Gift cards | MEDIUM | HIGH | P3 |

**Priority key:**
- P1: Must have for milestone launch — delivers the Core Value loop
- P2: Should have, add when possible after validation
- P3: Nice to have, future milestone

---

## New PermissionsEnum Cases

Add these cases to `app/Enums/PermissionsEnum.php` and register in `database/seeders/PermissionSeeder.php`. Run `php artisan db:seed --class=PermissionSeeder` after.

```php
// POS — extend existing POS_ACCESS
case POS_SALE = 'pos.sale';              // Complete a sale (checkout)
case POS_RETURNS = 'pos.returns';        // Process returns/refunds
// (POS_ACCESS already exists as 'pos.access')

// Dashboard
case DASHBOARD_VIEW = 'dashboard.view';

// Reports — base + per-domain
case REPORTS_VIEW = 'reports.view';              // Access reports landing
case REPORTS_SALES = 'reports.sales';            // Sales reports (by period/user/store/customer/top products/profit/tax/returns)
case REPORTS_INVENTORY = 'reports.inventory';    // Inventory reports (levels/valuation/expiry/stock movement)
case REPORTS_CASH = 'reports.cash';              // Cash register Z-report
case REPORTS_PURCHASES = 'reports.purchases';    // Purchases & receptions report
```

**Permission assignment guidance:**
- `ADMIN` role (super administrator): all new cases
- `SALESMAN` role: `POS_ACCESS`, `POS_SALE` (not `POS_RETURNS` unless trusted), `DASHBOARD_VIEW` (own metrics only — may need scope filtering), NO `REPORTS_*` (reports are managerial)
- Manager (future role or ADMIN): `DASHBOARD_VIEW`, all `REPORTS_*`, `POS_RETURNS`

**Existing permissions reused (no new cases needed):**
- `POS_ACCESS` — POS entry
- `SHIFTS_OPEN`, `SHIFTS_CLOSE`, `SHIFTS_MANAGE` — shift lifecycle
- `CASH_MOVEMENTS_CREATE` — cash in/out
- `SALES_CREATE`, `SALES_VIEW`, `SALES_VIEW_ALL`, `SALES_MANAGE` — sale create/view/manage (POS checkout uses `SALES_CREATE`)
- `STOCK_ALERTS_VIEW`, `ACTIVITY_LOGS_VIEW` — dashboard widgets

**Sidebar menu additions** (`resources/js/Layouts/Composables/useMenuItems.ts`):
- Dashboard — already present (home), gate with `can: 'dashboard.view'`
- Reports — new top-level entry, gate with `can: 'reports.view'`, child links for each report group gated by the specific `reports.*` permission

---

## Competitor Feature Analysis

Based on standard retail POS/reporting suites (Lightspeed Retail, Shopify POS, Square Retail, Vend, Loyverse POS — feature sets well-known in the retail POS category):

| Feature | Lightspeed Retail | Shopify POS | Square Retail | Loyverse POS | Our Approach |
|---------|-------------------|-------------|---------------|--------------|--------------|
| Cart + barcode + qty | ✓ | ✓ | ✓ | ✓ | POS-4/5/6 |
| Split tender | ✓ | ✓ | ✓ | ✓ | POS-10 (schema-ready) |
| Per-line + order discount | ✓ | ✓ | ✓ | ✓ | POS-7 (needs migration) + POS-8 |
| Hold/recall | ✓ | ✓ (park) | ✓ | ✓ | POS-14 |
| Returns/refunds | ✓ | ✓ | ✓ | ✓ | POS-17 |
| Print receipt | ✓ | ✓ | ✓ | ✓ | POS-15 |
| Shift open/close + Z-report | ✓ | ✓ | ✓ | ✓ | POS-2 + REP-8 |
| Cash in/out movements | ✓ | ✓ | ✓ | ✓ | POS-3 |
| Quick keys grid | ✓ | ✓ | ✓ | ✓ | POS-18 |
| Sales trend dashboard | ✓ | ✓ | ✓ | ✓ | DASH-6 |
| Top products | ✓ | ✓ | ✓ | ✓ | DASH-7 + REP-10 |
| Inventory valuation | ✓ | partial | ✓ | partial | REP-6 (Batch.unit_cost) |
| Profit margin | ✓ | partial | ✓ | partial | REP-11 (cost lookup) |
| CSV/Excel export | ✓ | ✓ | ✓ | ✓ | REP-16/18 |
| PDF export | ✓ | ✓ | partial | partial | REP-17 |
| Offline mode | ✓ | ✓ (Square Offline) | ✓ | ✓ (strong) | DIFF-1 (defer) |
| Loyalty | add-on | add-on | add-on | built-in | DIFF-2 (defer) |
| Gift cards | ✓ | ✓ | ✓ | ✓ | DIFF-3 (defer) |

**Our positioning:** Match the standard retail POS/report feature set (table stakes) without the hospitality or accounting drift. Differentiate later on analytics (ABC, slow-mover, predictive reorder) rather than copying Lightspeed's offline/loyalty add-on complexity in v1.

---

## Sources

- Project context: `.planning/PROJECT.md` (Core Value, Active requirements, Out of Scope, constraints)
- Existing data model: `app/Models/SalesOrder.php`, `SalesOrderItem.php`, `CashRegisterShift.php`, `CashRegisterMovement.php`, `Batch.php`, `ProductVariant.php` (verified field availability)
- Existing permissions: `app/Enums/PermissionsEnum.php` (baseline for new cases)
- Existing services: `app/Services/` listing (confirmed SalesOrderService, FifoStockDeductionService, StockAlertService, CashRegisterShiftService exist)
- Retail POS/dashboard/report feature standards: established category norms from Lightspeed Retail, Shopify POS, Square Retail, Loyverse POS feature sets (industry-common knowledge; web fetch attempts on Clover/Lightspeed returned JS-gated/404 pages, so categorization relies on documented retail POS standards and the verified existing schema)

---
*Feature research for: retail sales management — POS, dashboard, reports*
*Researched: 2026-06-21*