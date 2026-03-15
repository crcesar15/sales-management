# API — Sale Units

Routes are nested under `/products/{product}/variants/{variant}`.
Protected by `['auth', 'verified']` + `products.manage` permission.

## Route Table

| Method | Path | Action | Description |
|---|---|---|---|
| GET | `/products/{product}/variants/{variant}` | `ProductVariantController@show` | Variant detail + sale units |
| POST | `/products/{product}/variants/{variant}/sale-units` | `SaleUnitController@store` | Create sale unit |
| PUT | `/products/{product}/variants/{variant}/sale-units/{saleUnit}` | `SaleUnitController@update` | Update sale unit |
| DELETE | `/products/{product}/variants/{variant}/sale-units/{saleUnit}` | `SaleUnitController@destroy` | Delete sale unit |

## Variant Show Inertia Props
```json
{
  "product":  { "id": 1, "name": "T-Shirt", "measurement_unit": { "name": "Piece", "abbreviation": "pc" } },
  "variant": {
    "id": 10, "identifier": "TSH-R-S", "price": "19.99", "stock": 50, "status": "active",
    "option_values": [{ "value": "Red" }, { "value": "S" }],
    "sale_units": [
      { "id": 1, "name": "6-Pack",     "conversion_factor": 6,  "price": "99.99",  "status": "active" },
      { "id": 2, "name": "Crate of 24","conversion_factor": 24, "price": "359.99", "status": "inactive" }
    ]
  }
}
```

## Store Request
```json
POST /products/{product}/variants/{variant}/sale-units
{
  "name": "6-Pack",
  "conversion_factor": 6,
  "price": 99.99,
  "status": "active"
}
```

## Update Request
```json
PUT /products/{product}/variants/{variant}/sale-units/{saleUnit}
{
  "name": "Half Dozen",
  "conversion_factor": 6,
  "price": 95.00,
  "status": "inactive"
}
```

## Validation Rules
| Field | Rules |
|---|---|
| `name` | required, string, max:100, unique:product_variant_sale_units,name,{variant_id} (scoped per variant) |
| `conversion_factor` | required, integer, min:1 |
| `price` | required, numeric, min:0 |
| `status` | required, in:active,inactive |

## Responses
```json
// 201 — Created
{ "data": { "id": 3, "name": "6-Pack", "conversion_factor": 6, "price": "99.99", "status": "active" } }

// 200 — Updated (same structure)

// 200 — Deleted
{ "message": "Sale unit deleted." }

// 422 — Duplicate name per variant
{ "message": "The name has already been taken.", "errors": { "name": ["..."] } }

// 422 — conversion_factor < 1
{ "message": "The conversion factor must be at least 1.", "errors": { "conversion_factor": ["..."] } }
```

## POS Read Endpoint (Phase 4 preview)
Phase 4 will expose a POS-specific endpoint that returns all active sale units plus the derived base unit:
```
GET /pos/variants/{variant}/sale-options
→ [{ "name": "Piece", "conversion_factor": 1, "price": 19.99 }, { "name": "6-Pack", "conversion_factor": 6, "price": 99.99 }]
```
This endpoint is out of scope for Task 04 but the data structure should be kept compatible.
