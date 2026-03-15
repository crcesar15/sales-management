# Phase 6 — Task 03: Inventory Reports — API

## Endpoints

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/reports/inventory` | Render inventory report page (Inertia) | `reports.view_all` or `reports.view_own` |
| `GET` | `/reports/inventory/stock-levels` | Paginated stock levels JSON | Same |
| `GET` | `/reports/inventory/batches` | Paginated batch status JSON | Same |
| `GET` | `/reports/inventory/movements` | Paginated stock movement history JSON | Same |

> Prefer Inertia partial reloads (`only: ['stockLevels']`) over separate JSON endpoints to keep architecture simple.

## Query Parameters

### Stock Levels
| Param | Type | Notes |
|---|---|---|
| `store_id` | integer | Admin only |
| `category_id` | integer | Filter by product category |
| `brand_id` | integer | Filter by brand |
| `low_stock` | boolean | `1` = only below minimum |

### Batch Status
| Param | Type | Notes |
|---|---|---|
| `store_id` | integer | |
| `expiry_from` | `Y-m-d` | |
| `expiry_to` | `Y-m-d` | |
| `status` | string | `active`, `expired`, `depleted` |

### Movement History
| Param | Type | Notes |
|---|---|---|
| `store_id` | integer | |
| `product_variant_id` | integer | |
| `date_from` | `Y-m-d` | |
| `date_to` | `Y-m-d` | |
| `type` | string | `sale`, `adjustment`, `transfer_in`, `transfer_out`, `reception` |

## Response Shapes

**Stock Levels item**
```json
{ "sku": "SKU-001", "product": "Widget A", "store": "Main", "stock_quantity": 8, "minimum_stock_level": 20, "is_low_stock": true }
```

**Batch Status item**
```json
{ "batch_ref": "B-042", "product": "Widget A", "store": "Main", "expiry_date": "2026-01-15", "quantity": 50, "status": "active" }
```

**Movement History item**
```json
{ "type": "sale", "product_variant_id": 12, "sku": "SKU-001", "qty_change": -3, "store": "Main", "occurred_at": "2025-11-10T14:30:00Z" }
```
