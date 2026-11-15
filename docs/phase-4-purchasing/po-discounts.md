# Purchase-order discounts

Purchase orders support a discount per line followed by one discount for the order. Both accept `flat` and `percentage` using `DiscountType`.

## Calculation contract

1. `price` remains the normal all-in price of one purchasing unit.
2. Multiply by whole purchasing-unit `quantity` to obtain the gross line amount.
3. Apply the line's `discount_type` and `discount_value`. A flat discount is for the **whole line**, never per unit.
4. Store the monetary reduction in `discount_amount`, and the net line amount in `total`.
5. Sum net lines into `purchase_orders.sub_total`.
6. Apply the order's `discount_type` and `discount_value` to that subtotal; store the monetary result in `discount` and the payable amount in `total`.

`PurchaseOrderTotalsService` calculates amounts with decimal arithmetic and half-up monetary rounding. `AmountAllocationService` allocates the PO discount proportionally to net lines with stable largest-remainder allocation in cents. Free lines receive no share. Excessive discounts are rejected, not clamped.

The UI uses the authenticated `api.v1.purchase-orders.preview` endpoint for financial previews, including actual purchasing-unit/base-unit costs and normal replacement-pricing impact. The backend independently recalculates every save; submitted derived amounts are ignored. The existing amount-only `discount` input remains a flat-discount input when `discount_value` is absent. New clients send explicit `discount_type` and `discount_value`.

## Example

| Line | Gross | Line discount | Net | Allocated PO discount | Final cost |
| --- | ---: | ---: | ---: | ---: | ---: |
| 10 units at 20 | 200 | 10% = 20 | 180 | 18 | 162 |
| 5 units at 40 | 200 | 50 flat | 150 | 15 | 135 |

Gross subtotal is 400; line discounts total 70; the net subtotal is 330. A 10% PO discount is 33, giving a final total of 297. Actual base-unit costs are 16.20 and 27.00. Normal replacement references remain 20 and 40.

## Editing and receiving

Only drafts are editable. Percentages are persisted and recalculate when quantities or normal prices change. Header-only edits preserve line discounts. Item replacements preserve omitted discount settings by the scoped line ID, or an unambiguous existing catalog entry. Clear a discount with zero (or null), not by omitting its field. Changing the discount type in the UI resets the entered value to zero to avoid interpreting a currency amount as a percentage.

The existing receipt-cost calculation uses discounted net-line weights and the final order amount. Its denominator is the full ordered quantity and the snapshotted receiving conversion factor. Partial receipts therefore do not each deduct a full fixed line discount. Actual batch costs retain twelve decimal places and are never rewritten by subsequent supplier-price changes.

Receipt line prices and amounts remain normal/gross amounts and are labeled accordingly. Actual inventory costing comes from batch `unit_cost`. Purchase-order approval still publishes normal prices to replacement-cost references; neither discount lowers those references.

## Schema and deployment

The fresh-database creation migrations include line discount metadata plus persisted order discount type/value. Gross subtotal and total line discounts are derived display fields. No backfill command or extra modification migration is needed. Resetting an existing development database remains an explicitly approved operation.
