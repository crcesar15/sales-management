---
status: complete
phase: 01-critical-fixes-refactor
source: [01-01-SUMMARY.md, 01-02-SUMMARY.md, 01-03-SUMMARY.md, 01-04-SUMMARY.md, 01-05-SUMMARY.md, 01-06-SUMMARY.md, 01-07-SUMMARY.md]
started: 2026-06-22T16:24:58Z
updated: 2026-06-22T16:30:00Z
---

## Current Test

[testing complete]

## Tests

### 1. Settings Cache — Setting::get() returns value and Setting::set() invalidates immediately
expected: Open the application (any page that reads a setting, e.g. SalesOrders Create). Update a setting via the Settings UI (e.g. sales tax_rate). The new value is reflected on the next page load without manual cache clear, and no BadMethodCallException about "tags" is thrown in the logs or UI.
result: pass

### 2. Sales Order Tax Preview — Create page matches backend total
expected: On SalesOrders > Create, add line items and optionally a discount. The displayed subtotal, tax_amount, and total match what the backend saves (round((subTotal - discount) * (taxRate / 100), 2) for tax; total includes tax). Payment-difference validation compares against this taxed total.
result: pass

### 3. Sales Order Tax Preview — Edit page matches backend total
expected: On SalesOrders > Edit for an existing order, changing line items or discount recalculates tax_amount and total using the same formula as the backend. The totals shown on screen match the persisted totals after save.
result: pass

### 4. FIFO Stock Deduction — Oldest batch drains to zero then next batch
expected: Create a sales order that consumes more stock than the oldest batch has. The oldest batch is closed at zero (remaining_quantity = 0) and the next batch is drawn from. If total available stock is insufficient, an InvalidArgumentException is thrown and the order is rejected with a business-rule error message (not a 500).
result: pass

### 5. FIFO Stock Deduction — Transfer path auto-closes batch at zero
expected: Complete a stock transfer that fully drains a batch. The batch's remaining_quantity reaches 0 and is auto-closed; transferred_quantity is incremented on the destination. Insufficient stock throws InvalidArgumentException (not a generic Exception).
result: pass

### 6. Cash Register Shift — Allowed transitions work
expected: From the POS or cash-register UI, open a shift then close it (open -> closed). Re-open another shift and force-close it (open -> forced_close). Both operations succeed and the shift history shows the correct terminal status.
result: pass

### 7. Cash Register Shift — Terminal states reject transitions
expected: Attempt to close or add a movement to an already-closed or forced-closed shift. The service rejects the operation with an InvalidArgumentException and the UI shows a business-rule error (not a 500 server error).
result: pass

### 8. Service Delete Guards — Business-rule violations show friendly errors
expected: Try to delete a brand, category, customer, vendor, measurement unit, or product that has dependent records (e.g. a brand with products). The UI shows a business-rule error message (from InvalidArgumentException) and redirects back — not a 500 stack trace.
result: pass

### 9. Web Controller Catch Narrowing — Business errors handled, others propagate
expected: Trigger a business-rule violation via a web form (e.g. delete a category with products). The redirect-back message contains the business rule text. Triggering an unexpected error (e.g. DB down) no longer swallows the exception silently into a generic "error" flash — it propagates to the global handler.
result: pass

### 10. API Authorization — Unauthorized requests rejected
expected: Hit an API endpoint (activity-logs, batches, permissions, purchase-orders, settings, vendors, purchase orders) as an unauthenticated or unauthorized user. The response is 401/403, not 200 with data. Authenticated users with the correct permission receive 200 with the expected resource shape.
result: pass

### 11. API Mass-Assignment — Vendors and Purchase Orders use validated() only
expected: POST/PUT to /api/v1/vendors or /api/v1/purchase-orders with extra unexpected fields (e.g. "is_admin=1"). The extra fields are ignored (not persisted). The persisted record matches only the validated Form Request fields.
result: pass

### 12. Purchase Order API — Returns PurchaseOrderResource with correct status codes
expected: GET /api/v1/purchase-orders/{order} returns a JSON resource (not raw array). POST returns 201 with the created PurchaseOrderResource. PUT returns 200 with the updated PurchaseOrderResource.
result: pass

### 13. CashRegisterShiftResource — Movements serialized via whenLoaded
expected: GET an API endpoint that returns a cash register shift with movements eager loaded. The response includes a movements array. Request the same shift without movements eager loaded — movements is an empty array, not missing, and no other fields are dropped.
result: pass

### 14. StockTransferResource — All four date fields and variant identifier present
expected: GET an API endpoint returning a stock transfer. The resource includes cancelled_at, completed_at, created_at, updated_at (Carbon-safe ISO strings) and the productVariant identifier (not "sku"). All four date fields render without a "Call to a member function toISOString() on string" error.
result: pass

### 15. ApiCollection — {data, meta} pagination shape
expected: GET an API list endpoint that uses ApiCollection (e.g. /api/v1/vendors). The response body is { "data": [...], "meta": { "current_page", "last_page", "per_page", "total" } } with no duplicate Laravel pagination keys.
result: pass

### 16. Sort Column Whitelist — Invalid orderBy rejected safely
expected: From a list page (Users, Brands, Categories, Customers, Vendors, Stores, Measurement Units, Products, Catalog, Roles), send orderBy=malicious_column or orderDirection=invalid. The list still loads (sorted by a safe default, e.g. created_at) and does not 500 with a SQL error about an unknown column.
result: pass

### 17. CSRF — Axios requests authenticate correctly after login
expected: Log in to the app, then trigger an authenticated Axios API call (e.g. a composable fetch via useApi). The request succeeds (200) and is not rejected as 419 CSRF token mismatch. There is no X-XSRF-TOKEN header built from a meta DOM element; CSRF is handled by Axios withCredentials + withXSRFToken automatically.
result: pass

### 18. Daily Log Rotation — No unbounded browser.log growth
expected: After running the app for a while (or refreshing pages), check storage/logs/. The browser log is now split into daily files (browser-YYYY-MM-DD.log) with 7-day retention, not a single unbounded browser.log. The default stack also rotates daily (laravel-YYYY-MM-DD.log).
result: pass

### 19. Pest Coverage — Targeted Phase 1 success-criteria tests pass
expected: Run `php artisan test --compact --filter=FifoStockDeductionServiceTest`, `--filter=CashRegisterShiftTransitionsTest`, `--filter=SettingsCacheTest` (with CACHE_DRIVER=file), and `--filter=SalesOrderTaxTest`. Each filter reports all tests passing (17 total tests, ~50 assertions), no failures or todos remaining.
result: pass

## Summary

total: 19
passed: 19
issues: 0
pending: 0
skipped: 0

## Gaps

[none]