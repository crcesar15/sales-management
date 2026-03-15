# Task 01 — API: Stock Overview

## Endpoints

| Method | Path | Description |
|---|---|---|
| `GET` | `/inventory/stock` | Paginated stock overview (all variants) |
| `GET` | `/inventory/stock/{variant}` | Stock detail for a single variant across stores |
| `GET` | `/inventory/stock/stores` | Per-store stock summary (totals only) |

## Query Parameters — `GET /inventory/stock`
| Param | Type | Description |
|---|---|---|
| `store_id` | int | Filter to a specific store |
| `category_id` | int | Filter by product category |
| `brand_id` | int | Filter by brand |
| `low_stock` | bool | Only show variants below minimum |
| `search` | string | Product name / SKU search |
| `per_page` | int | Default 25 |

## Response Shape — `GET /inventory/stock`
```json
{
  "data": [
    {
      "variant_id": 12,
      "product_name": "Running Shoe",
      "variant_label": "Size 42 / Black",
      "sku": "RS-42-BLK",
      "minimum_stock_level": 10,
      "global_stock": 45,
      "low_stock": false,
      "per_store": [
        { "store_id": 1, "store_name": "Main Branch", "stock": 30 },
        { "store_id": 2, "store_name": "North Branch", "stock": 15 }
      ]
    }
  ],
  "meta": { "current_page": 1, "total": 120 }
}
```

## Response Shape — `GET /inventory/stock/{variant}`
```json
{
  "variant_id": 12,
  "product_name": "Running Shoe",
  "variant_label": "Size 42 / Black",
  "global_stock": 45,
  "per_store": [
    {
      "store_id": 1,
      "store_name": "Main Branch",
      "stock": 30,
      "batches_count": 2
    }
  ]
}
```

## Notes
- All endpoints return Inertia props (not JSON API) — shapes above reflect the `props` structure
- Pagination handled server-side; frontend uses PrimeVue `DataTable` with lazy loading
- `low_stock` is computed: `global_stock < minimum_stock_level` (null minimum = never low)
