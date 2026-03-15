# Task 02: Vendor Catalog — API

## Endpoints

| Method | Path                         | Description                          | Permission     |
|--------|------------------------------|--------------------------------------|----------------|
| GET    | `/vendors/{vendor}/catalog`  | List catalog entries for a vendor    | vendors.manage |
| POST   | `/vendors/{vendor}/catalog`  | Create catalog entry                 | vendors.manage |
| PUT    | `/catalog/{entry}`           | Update catalog entry                 | vendors.manage |
| DELETE | `/catalog/{entry}`           | Delete catalog entry                 | vendors.manage |
| GET    | `/catalog/variants`          | Active entries for PO builder        | purchase_orders.create |

## Request Body — POST/PUT catalog entry

```json
{
  "vendor_id": 1,
  "product_variant_id": 42,
  "price": 24.50,
  "payment_terms": "NET30",
  "details": "Sold in boxes of 12",
  "status": "active",
  "purchase_unit_id": 5,
  "conversion_factor": 12,
  "minimum_order_quantity": 2,
  "lead_time_days": 7
}
```

## Response — Catalog Entry Resource

```json
{
  "id": 10,
  "vendor": { "id": 1, "fullname": "Acme Supplies" },
  "product_variant": { "id": 42, "name": "Cola 330ml" },
  "purchase_unit": { "id": 5, "name": "Box" },
  "price": 24.50,
  "conversion_factor": 12,
  "minimum_order_quantity": 2,
  "lead_time_days": 7,
  "status": "active"
}
```

## Error Responses

| Status | Scenario                                           |
|--------|----------------------------------------------------|
| 422    | Duplicate vendor + variant, conversion_factor < 1  |
| 404    | Vendor or product variant not found                |
| 403    | Missing `vendors.manage` permission                |
