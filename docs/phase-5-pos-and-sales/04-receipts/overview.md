# Task 04 — Receipts

## What
Auto-generated digital receipt for every paid sales order. Accessible via a public URL (no login required) and printable via browser.

## Why
Provides customers with proof of purchase without requiring a dedicated receipt printer or third-party library. The shareable URL enables digital delivery.

## Requirements
- Every `sales_order` gets a unique `token` (UUID) on creation
- Public receipt URL: `/receipts/{token}` — no authentication required
- Content: store name, logo, address; cashier name; date; item list; discount; tax; total; payment method
- Header and footer text customisable via `settings` group `receipt`
- Print via `window.print()` — print stylesheet hides all UI chrome
- No external PDF/print libraries

## Acceptance Criteria
- [ ] Receipt URL is accessible without login using valid token
- [ ] Invalid token returns 404
- [ ] Receipt displays all required fields (store info, items, totals)
- [ ] `window.print()` produces a clean receipt (no navbar, sidebar, buttons)
- [ ] Header/footer text is pulled from `settings` group `receipt`
- [ ] Store logo displays if configured in settings
- [ ] Receipt is readable on mobile (for sharing via link)

## Dependencies
- `sales_orders.token` column (UUID, unique) — added in Task 03 migrations
- `sales_order_items` with product/variant name snapshots or eager-loaded relations
- `settings` — store info + receipt customisation
- Spatie Media Library — store logo retrieval

## Notes
- Receipt page uses a minimal layout (no sidebar, no auth guards)
- Product/variant names should be readable even if products are later deleted — consider name snapshots or eager load with `withTrashed` if soft-deletes apply
- The receipt token is NOT the order ID — do not expose sequential IDs in the public URL
