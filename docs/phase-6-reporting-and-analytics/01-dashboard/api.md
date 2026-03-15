# Phase 6 — Task 01: Dashboard — API

## Endpoints

| Method | Path | Description | Auth / Permission |
|---|---|---|---|
| `GET` | `/dashboard` | Render dashboard page with initial KPIs | Any authenticated user |
| `GET` | `/dashboard/kpis` | Poll endpoint — returns latest KPI values | Any authenticated user |
| `GET` | `/dashboard/charts` | Deferred — revenue trend + top products | Any authenticated user |
| `GET` | `/dashboard/alerts` | Active low-stock + expiry alerts | Any authenticated user |

> All endpoints scope data by role automatically. Sales Rep responses exclude other users' data.

## Query Parameters

| Param | Type | Applies To | Notes |
|---|---|---|---|
| `store_id` | integer | `/kpis`, `/charts`, `/alerts` | Admin only; ignored for Sales Rep |
| `date_from` | date `Y-m-d` | `/kpis` | Defaults to start of current month |
| `date_to` | date `Y-m-d` | `/kpis` | Defaults to today |

## Response Shapes

**`GET /dashboard/kpis`**
```json
{
  "today_revenue": 4250.00,
  "monthly_revenue": 87430.00,
  "low_stock_count": 12,
  "pending_po_count": 3,
  "top_products": [
    { "product_id": 5, "name": "Widget A", "qty_sold": 142 }
  ]
}
```

**`GET /dashboard/charts`**
```json
{
  "revenue_trend": [
    { "month": "2025-10", "revenue": 72000.00 },
    { "month": "2025-11", "revenue": 81500.00 }
  ],
  "top_products_chart": [
    { "label": "Widget A", "value": 142 }
  ]
}
```

**`GET /dashboard/alerts`**
```json
{
  "alerts": [
    { "id": 1, "type": "low_stock", "message": "SKU-001 below min level", "store": "Main Branch" },
    { "id": 2, "type": "expiry", "message": "Batch B-042 expires in 7 days", "store": "Main Branch" }
  ],
  "total": 2
}
```

## Notes
- `/dashboard` is an Inertia response; all others return JSON for polling/deferred loading
- Inertia partial reloads use `only: ['kpis']` — no dedicated JSON route needed if using Inertia polling
- Consider consolidating into a single Inertia page with `router.reload({ only: ['kpis', 'alerts'] })`
