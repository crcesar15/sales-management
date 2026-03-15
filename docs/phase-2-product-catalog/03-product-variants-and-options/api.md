# API — Product Variants & Options

All routes are nested under `/products/{product}` — Inertia web routes.
Protected by `['auth', 'verified']` + `products.manage` permission.

## Route Table

| Method | Path | Action | Description |
|---|---|---|---|
| GET | `/products/{product}` | `ProductController@show` | Product detail + options + variants |
| POST | `/products/{product}/options` | `ProductOptionController@store` | Add option to product |
| PUT | `/products/{product}/options/{option}` | `ProductOptionController@update` | Rename option |
| DELETE | `/products/{product}/options/{option}` | `ProductOptionController@destroy` | Remove option |
| POST | `/products/{product}/options/{option}/values` | `OptionValueController@store` | Add value to option |
| DELETE | `/products/{product}/options/{option}/values/{value}` | `OptionValueController@destroy` | Remove option value |
| POST | `/products/{product}/variants/generate` | `ProductVariantController@generate` | Auto-generate variants |
| POST | `/products/{product}/variants` | `ProductVariantController@store` | Add single variant (manual) |
| PUT | `/products/{product}/variants/{variant}` | `ProductVariantController@update` | Edit variant |
| DELETE | `/products/{product}/variants/{variant}` | `ProductVariantController@destroy` | Remove variant |

## Product Show Inertia Props
```json
{
  "product": {
    "id": 1, "name": "T-Shirt",
    "options": [
      { "id": 1, "name": "Color", "values": [{ "id": 1, "value": "Red" }, { "id": 2, "value": "Blue" }] },
      { "id": 2, "name": "Size",  "values": [{ "id": 3, "value": "S" }, { "id": 4, "value": "M" }] }
    ],
    "variants": [
      {
        "id": 10, "identifier": "TSH-R-S", "price": "19.99", "stock": 50,
        "status": "active",
        "option_values": [{ "id": 1, "value": "Red" }, { "id": 3, "value": "S" }]
      }
    ]
  }
}
```

## Auto-Generate Request
```json
POST /products/{product}/variants/generate
{
  "options": [
    { "name": "Color", "values": ["Red", "Blue"] },
    { "name": "Size",  "values": ["S", "M", "L"] }
  ]
}
```
Response: `201` with `{ "generated": 6, "variants": [...] }`

## Manual Variant Store Request
```json
POST /products/{product}/variants
{
  "identifier": "SKU-001",
  "price": 19.99,
  "stock": 0,
  "status": "active",
  "option_value_ids": [1, 3]
}
```

## Variant Update Request
```json
PUT /products/{product}/variants/{variant}
{
  "identifier": "SKU-001-v2",
  "price": 24.99,
  "stock": 10,
  "status": "inactive"
}
```

## Error Responses
```json
// 422 — variants already exist, auto-generate blocked
{ "message": "Cannot auto-generate: this product already has variants." }

// 422 — duplicate option value combination
{ "message": "A variant with this option combination already exists." }

// 422 — option value in use
{ "message": "Cannot delete option value: it is assigned to one or more variants." }
```
