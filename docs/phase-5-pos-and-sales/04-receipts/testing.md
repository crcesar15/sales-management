# Testing — Receipts

## Test File Locations
```
tests/Feature/ReceiptTest.php
tests/Unit/Models/SalesOrderTokenTest.php
```

## Feature Test Cases

### Public Receipt Page (`GET /receipts/{token}`)
- Valid token for a `paid` order returns 200
- Valid token for a `draft` order returns 404
- Valid token for a `cancelled` order returns 404
- Invalid / non-existent token returns 404
- Response includes store name, address, phone
- Response includes cashier name
- Response includes all order items with correct quantities and prices
- Response includes sub_total, discount, tax_amount, total
- Response includes receipt_header and receipt_footer from settings
- Page is accessible without authentication (no redirect)

### Receipt Redirect (`GET /sales-orders/{order}/receipt`)
- Authenticated user is redirected to `/receipts/{token}`
- Unauthenticated user is redirected to login

### Settings Integration
- When `receipt_header` setting is set, it appears in receipt props
- When `receipt_footer` setting is set, it appears in receipt props
- When store logo is configured, `logo_url` is present in store props

### Token Generation
- Every new `SalesOrder` gets a unique `token` on creation
- Two orders never share the same token
- Token is a valid UUID format

## Unit Test Cases (`SalesOrderTokenTest`)
- `token` is auto-set in `creating` model event
- `token` is not overwritten if already set

## Coverage Goals
- All token validity and status guard paths
- Public access (no auth) confirmed
- Settings population confirmed
- Receipt content completeness (all required fields present)
