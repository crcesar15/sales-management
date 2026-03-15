# API — Product Management

All routes are Inertia web routes protected by `['auth', 'verified']` middleware + Policy authorization.
Pending media endpoints return JSON (not Inertia).

## Route Table

| Method | Path | Action | Returns |
|---|---|---|---|
| GET | `/products` | `ProductController@index` | Inertia `Products/Index` |
| GET | `/products/create` | `ProductController@create` | Inertia `Products/Create` |
| POST | `/products` | `ProductController@store` | Redirect to show |
| GET | `/products/{product}` | `ProductController@show` | Inertia `Products/Show` |
| GET | `/products/{product}/edit` | `ProductController@edit` | Inertia `Products/Edit` |
| PUT | `/products/{product}` | `ProductController@update` | Redirect to show |
| DELETE | `/products/{product}` | `ProductController@destroy` | JSON |
| PATCH | `/products/{id}/restore` | `ProductController@restore` | JSON |
| POST | `/products/media/pending` | `ProductMediaController@store` | JSON 201 |
| DELETE | `/products/media/pending/{uuid}` | `ProductMediaController@destroy` | JSON |

## Inertia Index Props
```json
{
  "products":   { "data": [...], "meta": { "per_page": 20, "total": 98 } },
  "filters":    { "search": "", "brand_id": null, "category_id": null, "status": null },
  "brands":     [{ "id": 1, "name": "Coca-Cola" }],
  "categories": [{ "id": 1, "name": "Beverages" }]
}
```

## Store / Update Request Fields
| Field | Rules |
|---|---|
| `name` | required, string, max:255 |
| `description` | nullable, string, max:350 |
| `status` | required, in:active,inactive,archived |
| `brand_id` | nullable, exists:brands,id (not trashed) |
| `measurement_unit_id` | nullable, exists:measurement_units,id (not trashed) |
| `category_ids` | nullable, array; each exists:categories,id (not trashed) |
| `pending_media_uuids` | nullable, array of UUIDs |
| `remove_media_ids` | nullable, array of integers |

> Update uses `sometimes` on `name` and `status` to support partial updates.

## Product Resource Shape
```json
{
  "id": 1, "name": "Coca-Cola 500ml", "description": "...", "status": "active",
  "brand": { "id": 1, "name": "Coca-Cola" },
  "measurement_unit": { "id": 2, "name": "Piece", "abbreviation": "pc" },
  "categories": [{ "id": 1, "name": "Beverages" }],
  "images": [{ "id": 10, "url": "...", "thumb": "..." }],
  "variants_count": 3,
  "deleted_at": null
}
```

## Pending Media Response
```json
// POST /products/media/pending → 201
{ "uuid": "a1b2c3...", "url": "http://localhost/storage/pending/a1b2c3.../file.jpg" }
```

## Error Responses
```json
// 422 — delete blocked by active variants
{ "message": "Cannot delete product: it has active or inactive variants. Archive all variants first." }

// 422 — validation
{ "message": "The name field is required.", "errors": { "name": ["..."] } }
```
