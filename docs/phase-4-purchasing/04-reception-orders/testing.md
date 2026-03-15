# Task 04: Reception Orders — Testing

## Test File Locations

| File                                                          | Type    |
|---------------------------------------------------------------|---------|
| `tests/Feature/ReceptionOrders/ReceptionOrderCrudTest.php`    | Feature |
| `tests/Feature/ReceptionOrders/ReceptionOrderCompleteTest.php`| Feature |
| `tests/Unit/Services/ReceptionOrderServiceTest.php`           | Unit    |

## Test Cases

### ReceptionOrderCrudTest
- **Create**: valid reception against an `approved` PO creates a `pending` reception
- **Store required**: missing `store_id` returns 422
- **PO status guard**: creating reception against `draft` PO returns 422
- **PO status guard**: creating reception against `paid` PO returns 422
- **Update**: can update a `pending` reception's notes and line items
- **Cancel**: cancelling a pending reception sets status to `cancelled`
- **Permission gate**: user without `reception_orders.manage` gets 403
- **List**: receptions for a specific PO are returned via `/purchase-orders/{po}/receptions`

### ReceptionOrderCompleteTest
- **Complete success**: completing a reception updates stock correctly (`qty × conversion_factor`)
- **Batch creation**: one batch record is created per line item with correct `initial_quantity`
- **Batch store**: batch `store_id` matches reception `store_id`
- **Expiry date**: batch `expiry_date` is set from line item when provided
- **Expiry optional**: batch is created without expiry when line item has no `expiry_date`
- **Atomicity**: if stock update fails, batches are not created (transaction rollback)
- **Already completed**: completing a `completed` reception returns 409
- **Activity log**: completion creates an activity log entry

### ReceptionOrderServiceTest (Unit)
- `complete()` applies correct `conversion_factor` per variant
- `complete()` handles `conversion_factor = 1` (no purchase unit) correctly
- Missing catalog entry falls back gracefully or throws descriptive exception

## Coverage Goals
- [ ] Stock increment verified with `assertDatabaseHas` after completion
- [ ] Batch count matches number of line items
- [ ] Transaction rollback tested via mocking a failure mid-completion
- [ ] All PO status guards tested
- [ ] `store_id` migration column existence verified

## Notes
- Use `RefreshDatabase` + factories for PO, vendor, catalog, store, product_variants
- Test `conversion_factor` edge cases: `1`, `12`, and large values
