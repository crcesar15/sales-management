# Task 03: Purchase Orders — Testing

## Test File Locations

| File                                                       | Type    |
|------------------------------------------------------------|---------|
| `tests/Feature/PurchaseOrders/PurchaseOrderCrudTest.php`   | Feature |
| `tests/Feature/PurchaseOrders/PurchaseOrderWorkflowTest.php` | Feature |
| `tests/Unit/Services/PurchaseOrderServiceTest.php`         | Unit    |

## Test Cases

### PurchaseOrderCrudTest
- **Create**: valid payload with catalog-backed items creates PO in `draft` status
- **Price snapshot**: price on line item matches catalog price at creation time (not client-supplied)
- **Totals**: `sub_total`, `total` are computed correctly; `total = sub_total - discount`
- **Invalid variant**: adding a variant not in vendor's active catalog returns 422
- **Update draft**: can update notes, expected arrival date, and line items on a draft PO
- **Cannot update non-draft**: updating an `approved` PO returns 422 or 403
- **Permission gate**: user without `purchase_orders.create` gets 403

### PurchaseOrderWorkflowTest
- **submit**: `draft → awaiting_approval` succeeds for user with `purchase_orders.create`
- **approve**: `awaiting_approval → approved` succeeds for user with `purchase_orders.approve`
- **approve blocked**: user without `purchase_orders.approve` cannot approve
- **send**: `approved → sent` succeeds
- **pay**: `sent → paid` succeeds
- **cancel from draft**: succeeds
- **cancel from approved**: succeeds
- **cancel blocked from sent**: returns 422
- **cancel blocked from paid**: returns 422
- **Activity log**: each status transition creates an activity log entry

### PurchaseOrderServiceTest (Unit)
- `transition()` throws on invalid status jump (e.g., `draft → paid`)
- `recalculate()` computes correct totals with discount
- Allowed transitions map is exhaustive

## Coverage Goals
- [ ] Full happy-path workflow (draft → paid) tested end-to-end
- [ ] Every invalid transition tested
- [ ] Price snapshot logic tested (no client price accepted)
- [ ] Cancellation guard for `sent` and `paid` tested
- [ ] Activity log entries verified after transitions

## Notes
- Use `actingAs($user)->post(...)` with permission-seeded users
- Assert activity log via `assertDatabaseHas('activity_log', [...])`
