# API — Product Variant Units

Routes are nested under `/products/{product}/variants/{variant}`.
Protected by `['auth', 'verified']` + `products.manage` permission.

## Route Table

| Method | Path | Action | Description |
|---|---|---|---|
| GET | `/products/{product}/variants/{variant}` | `ProductVariantController@show` | Variant detail + sale units + purchase units |
| GET | `/products/{product}/variants/{variant}/units?type=sale` | `ProductVariantUnitController@index` | List units filtered by type |
| POST | `/products/{product}/variants/{variant}/units` | `ProductVariantUnitController@store` | Create unit (sale or purchase) |
| PUT | `/products/{product}/variants/{variant}/units/{unit}` | `ProductVariantUnitController@update` | Update unit |
| DELETE | `/products/{product}/variants/{variant}/units/{unit}` | `ProductVariantUnitController@destroy` | Delete unit |

## Variant Show Inertia Props
```json
{
  "product":  { "id": 1, "name": "T-Shirt", "measurement_unit": { "name": "Piece", "abbreviation": "pc" } },
  "variant": {
    "id": 10, "identifier": "TSH-R-S", "price": "19.99", "stock": 50, "status": "active",
    "option_values": [{ "value": "Red" }, { "value": "S" }],
    "sale_units": [
      { "id": 1, "type": "sale", "name": "6-Pack",     "conversion_factor": 6,  "price": "99.99",  "status": "active", "sort_order": 0 },
      { "id": 2, "type": "sale", "name": "Crate of 24","conversion_factor": 24, "price": "359.99", "status": "inactive", "sort_order": 1 }
    ],
    "purchase_units": [
      { "id": 3, "type": "purchase", "name": "Small Box", "conversion_factor": 6,  "price": null, "status": "active", "sort_order": 0 },
      { "id": 4, "type": "purchase", "name": "Big Box",   "conversion_factor": 24, "price": null, "status": "active", "sort_order": 1 }
    ]
  }
}
```

## Store Request — Sale Unit
```json
POST /products/{product}/variants/{variant}/units
{
  "type": "sale",
  "name": "6-Pack",
  "conversion_factor": 6,
  "price": 99.99,
  "status": "active",
  "sort_order": 0
}
```

## Store Request — Purchase Unit
```json
POST /products/{product}/variants/{variant}/units
{
  "type": "purchase",
  "name": "Small Box",
  "conversion_factor": 6,
  "price": null,
  "status": "active",
  "sort_order": 0
}
```

## Update Request
```json
PUT /products/{product}/variants/{variant}/units/{unit}
{
  "name": "Half Dozen",
  "conversion_factor": 6,
  "price": 95.00,
  "status": "inactive",
  "sort_order": 1
}
```

Note: `type` is NOT changeable after creation. A sale unit cannot become a purchase unit.

## Validation Rules
| Field | Rules |
|---|---|
| `type` | required, in:sale,purchase (store only; immutable on update) |
| `name` | required, string, max:100, unique per (variant, type) |
| `conversion_factor` | required, integer, min:1 |
| `price` | nullable, numeric, min:0; required when type=sale |
| `status` | required, in:active,inactive |
| `sort_order` | nullable, integer, min:0 |

## Responses
```json
// 201 — Created
{ "data": { "id": 3, "type": "sale", "name": "6-Pack", "conversion_factor": 6, "price": "99.99", "status": "active", "sort_order": 0 } }

// 200 — Updated (same structure)

// 200 — Deleted
{ "message": "Unit deleted." }

// 422 — Duplicate name per variant per type
{ "message": "The name has already been taken.", "errors": { "name": ["..."] } }

// 422 — conversion_factor < 1
{ "message": "The conversion factor must be at least 1.", "errors": { "conversion_factor": ["..."] } }

// 422 — Missing price on sale type
{ "message": "The price field is required when type is sale.", "errors": { "price": ["..."] } }
```

## POS Read Endpoint (Phase 4 preview)
Phase 4 will expose a POS-specific endpoint that returns all active sale units plus the derived base unit:
```
GET /pos/variants/{variant}/sale-options
→ [{ "name": "Piece", "abbreviation": "pc", "conversion_factor": 1, "price": 19.99 }, { "name": "6-Pack", "conversion_factor": 6, "price": 99.99 }]
```
This endpoint is out of scope for Task 05 but the data structure should be kept compatible.

## Purchase Unit Catalog Reference (Phase 4 preview)
Phase 4 vendor catalog will reference purchase units:
```
GET /api/v1/vendors/{vendor}/catalog
→ [
  { "variant_id": 10, "unit_id": 3, "unit_name": "Small Box", "conversion_factor": 6, "price": "5.00", "min_order_qty": 10, "lead_time_days": 3 },
  { "variant_id": 10, "unit_id": 4, "unit_name": "Big Box", "conversion_factor": 24, "price": "18.00", "min_order_qty": 5, "lead_time_days": 5 }
]
```
This endpoint is out of scope for Task 05 but the data structure should be kept compatible.
