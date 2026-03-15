# API — Categories, Brands & Measurement Units

All routes use Inertia (web) — served as `Inertia::render()` responses, not pure JSON.
Protected by `auth` + `verified` middleware; permission check via Policy/`authorize()`.

## Route Table

| Method | Path | Action | Description |
|---|---|---|---|
| GET | `/catalog/setup` | `CatalogSetupController@index` | Main tabbed page (all three entities) |
| POST | `/catalog/setup/categories` | `CategoryController@store` | Create category |
| PUT | `/catalog/setup/categories/{category}` | `CategoryController@update` | Update category |
| DELETE | `/catalog/setup/categories/{category}` | `CategoryController@destroy` | Soft-delete category |
| PATCH | `/catalog/setup/categories/{id}/restore` | `CategoryController@restore` | Restore category |
| POST | `/catalog/setup/brands` | `BrandController@store` | Create brand |
| PUT | `/catalog/setup/brands/{brand}` | `BrandController@update` | Update brand |
| DELETE | `/catalog/setup/brands/{brand}` | `BrandController@destroy` | Soft-delete brand |
| PATCH | `/catalog/setup/brands/{id}/restore` | `BrandController@restore` | Restore brand |
| POST | `/catalog/setup/measurement-units` | `MeasurementUnitController@store` | Create unit |
| PUT | `/catalog/setup/measurement-units/{unit}` | `MeasurementUnitController@update` | Update unit |
| DELETE | `/catalog/setup/measurement-units/{unit}` | `MeasurementUnitController@destroy` | Soft-delete unit |
| PATCH | `/catalog/setup/measurement-units/{id}/restore` | `MeasurementUnitController@restore` | Restore unit |

## Index Response (Inertia props)
The single index route passes all three paginated collections to the Vue page:
```json
{
  "categories": { "data": [...], "meta": { "current_page": 1, "per_page": 15, "total": 40 } },
  "brands":     { "data": [...], "meta": { ... } },
  "units":      { "data": [...], "meta": { ... } },
  "filters":    { "search": "bev", "trashed": false },
  "activeTab":  "categories"
}
```

## Store / Update Request Shape
All three entities share the same minimal shape:
```json
// Category / Brand
{ "name": "Beverages" }

// Measurement Unit
{ "name": "Kilogram", "abbreviation": "kg" }
```

## Validation Rules
| Field | Category | Brand | Unit |
|---|---|---|---|
| `name` | required, string, max:50, unique (ignore self on update) | required, string, max:50 | required, string, max:100 |
| `abbreviation` | — | — | required, string, max:10 |

## Error Responses
```json
// 422 — Validation failure
{ "message": "The name has already been taken.", "errors": { "name": ["..."] } }

// 422 — Business rule (deletion guard)
{ "message": "Cannot delete category: it is assigned to one or more active products." }

// 403 — Missing permission
{ "message": "This action is unauthorized." }
```

## Restore Route Note
`restore` uses `{id}` (plain integer) not route model binding — because Eloquent scopes exclude soft-deleted records by default.
Use `Category::withTrashed()->findOrFail($id)` in the controller.
