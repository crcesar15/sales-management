# Phase 6 — Task 01: Dashboard — Overview

## What
Role-based dashboard displayed immediately after login. Provides KPI cards, active alert banners, and charts summarising store performance. Data is scoped by role and refreshed periodically via polling.

## Why
Gives Admins a cross-store operational view and Sales Reps a focused view of their own store's activity, surfacing actionable signals (low stock, expiry alerts, pending POs) without navigating deep into the system.

## Requirements
- KPI cards filterable by store (Admin only) and date range
  - Today's revenue (paid sales orders, current day)
  - Monthly revenue (current calendar month)
  - Low stock product variant count (below `minimum_stock_level`)
  - Pending purchase orders count
  - Top 5 selling products by quantity, current month
- Active alerts banner: low stock + expiry alerts from Phase 3
- Charts: monthly revenue trend (line, last 6 months), top products (bar)
- Admin sees all stores; Sales Rep sees only their assigned store
- Sales Rep KPIs scoped to their own sales (not full store revenue)
- Periodic refresh via polling (no WebSockets)

## Acceptance Criteria
- [ ] KPI cards render correct values for both Admin and Sales Rep roles
- [ ] Store filter visible only to Admin; defaults to "All Stores"
- [ ] Date range picker filters all KPI cards simultaneously
- [ ] Alert banner is hidden when there are no active alerts
- [ ] Revenue trend line chart shows last 6 months with no future months
- [ ] Top products bar chart reflects current month's quantity sold
- [ ] Polling refreshes data every 60 seconds without full page reload
- [ ] Sales Rep cannot access another store's data via URL manipulation
- [ ] Charts are responsive and render on mobile

## Dependencies
- Phase 3: stock alerts table (`stock_alerts`)
- Phase 4: sales orders, sales order items
- Phase 5: purchase orders, product variants with `minimum_stock_level`
- PrimeVue Charts (Chart.js wrapper)
- Ziggy (named route helpers in Vue)
- Spatie Laravel Permission (`reports.view_all`, `reports.view_own`)

## Notes
- Use `Inertia::render` with deferred props for chart data to keep initial load fast
- Polling implemented with `router.reload({ only: [...] })` on an interval
- Revenue figures based on `sales_orders.status = 'paid'`
- "Top 5" query uses `SUM(sales_order_items.quantity)` grouped by product
