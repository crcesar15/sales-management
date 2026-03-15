# Phase 6 — Task 04: Purchasing Reports — API

## Endpoints

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/reports/purchasing` | Render purchasing report page (Inertia) | `reports.view_all` |
| `GET` | `/reports/purchasing/po-summary` | PO list + metrics (partial reload) | `reports.view_all` |
| `GET` | `/reports/purchasing/vendor-spend` | Vendor spend aggregation | `reports.view_all` |
| `GET` | `/reports/purchasing/reception-accuracy` | Line-item accuracy table | `reports.view_all` |

> All endpoints return 403 for any user without `reports.view_all`. No `view_own` variant for purchasing.

## Query Parameters

### PO Summary
| Param | Type | Notes |
|---|---|---|
| `vendor_id` | integer | |
| `status` | string | `pending`, `approved`, `received`, `partially_received`, `cancelled` |
| `date_from` | `Y-m-d` | |
| `date_to` | `Y-m-d` | |

### Vendor Spend
| Param | Type | Notes |
|---|---|---|
| `date_from` | `Y-m-d` | |
| `date_to` | `Y-m-d` | |

### Reception Accuracy
| Param | Type | Notes |
|---|---|---|
| `vendor_id` | integer | |
| `date_from` | `Y-m-d` | |
| `date_to` | `Y-m-d` | |
| `variance_type` | string | `over`, `under`, `exact` |

## Response Shapes

**PO Summary metrics**
```json
{
  "total_spend": 245000.00,
  "pending_count": 5,
  "avg_lead_time_days": 4.3
}
```

**Vendor Spend item**
```json
{ "vendor_id": 3, "vendor_name": "Supplier Co.", "order_count": 12, "total_ordered_value": 98000.00 }
```

**Reception Accuracy item**
```json
{
  "po_id": 201, "po_ref": "PO-2025-0201", "vendor": "Supplier Co.",
  "product": "Widget A", "sku": "SKU-001",
  "qty_ordered": 100, "qty_received": 92,
  "variance": -8, "variance_pct": -8.0
}
```
