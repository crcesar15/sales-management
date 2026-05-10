# Task 03: Catalog — API

## Endpoints

| Method | Path                     | Description                                | Permission     |
|--------|--------------------------|--------------------------------------------|----------------|
| GET    | `/catalog`               | Product-centric catalog listing (Inertia)  | catalog.view   |
| GET    | `/api/v1/catalog`        | API: list catalog entries grouped by variant | catalog.view |
| GET    | `/api/v1/catalog/variants/{variantId}/vendors` | API: vendor offerings for a specific variant | catalog.view |

> Create, edit, and delete are handled through the vendor-scoped routes documented in Task 02.

## Response — GET `/api/v1/catalog`

Returns all catalog entries with eager-loaded relationships, suitable for client-side grouping by product variant:

```json
{
  "data": [
    {
      "id": 10,
      "vendor_id": 1,
      "product_variant_id": 42,
      "unit_id": 5,
      "price": 24.50,
      "payment_terms": "NET30",
      "details": "Sold in boxes of 12",
      "status": "active",
      "minimum_order_quantity": 2,
      "lead_time_days": 7,
      "vendor": { "id": 1, "fullname": "Acme Supplies" },
      "product_variant": {
        "id": 42,
        "name": "Cola 330ml",
        "identifier": "COL-330",
        "product": { "id": 5, "name": "Cola" },
        "values": [
          { "option_name": "Size", "value": "330ml" }
        ]
      },
      "purchase_unit": { "id": 5, "name": "Box", "conversion_factor": 12 }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 28
  }
}
```

## Response — GET `/api/v1/catalog/variants/{variantId}/vendors`

Returns catalog entries for a specific product variant, sorted by price (lowest first):

```json
{
  "data": [
    {
      "id": 10,
      "vendor_id": 1,
      "price": 24.50,
      "minimum_order_quantity": 2,
      "lead_time_days": 7,
      "vendor": { "id": 1, "fullname": "Acme Supplies" },
      "purchase_unit": { "id": 5, "name": "Box", "conversion_factor": 12 }
    },
    {
      "id": 15,
      "vendor_id": 3,
      "price": 22.00,
      "minimum_order_quantity": 5,
      "lead_time_days": 14,
      "vendor": { "id": 3, "fullname": "Beta Distributors" },
      "purchase_unit": null
    }
  ]
}
```

## Query Parameters

| Parameter    | Type     | Default    | Description                        |
|-------------|----------|------------|------------------------------------|
| `filter`    | string   | null       | Search by product name              |
| `status`    | string   | `active`   | Filter by status: `active`, `inactive`, `all` |
| `vendor_id` | integer  | null       | Filter by vendor                    |
| `per_page`  | integer  | 10         | Items per page                     |
| `sortField` | string   | `product_name` | Sort field                     |
| `sortDirection` | string | `asc`   | Sort direction                      |

## Error Responses

| Status | Scenario                                     |
|--------|----------------------------------------------|
| 403    | Missing `catalog.view` permission            |
| 404    | Product variant not found (vendor offerings) |