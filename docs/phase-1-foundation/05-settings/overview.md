# Settings — Overview

## What
A key-value settings store that holds configurable system-wide values grouped by functional area. Settings are managed by admins and consumed by other modules (POS, receipts, inventory alerts). All settings are cached after the first read and invalidated whenever a value is updated.

## Why
System behavior must be configurable without code changes. Store name, tax rate, receipt layout, and inventory alert thresholds need to be adjustable by the admin. A simple key-value approach is flexible and easy to extend without new migrations.

## Phase 1 Settings Groups & Keys

| Group | Key | Type | Default | Used By |
|---|---|---|---|---|
| `general` | `business_name` | string | `My Store` | UI |
| `general` | `business_address` | string | `` | — |
| `general` | `business_phone` | string | `` | — |
| `general` | `timezone` | string | `UTC` | Reports, timestamps |
| `tax` | `tax_rate` | numeric | `0` | POS, receipts (future) |

**Deferred to later phases** (added when consuming modules are built):

| Group | Key | Type | Default | Phase |
|---|---|---|---|---|
| `receipt` | `receipt_header` | text | `` | POS/Receipt module |
| `receipt` | `receipt_footer` | text | `` | POS/Receipt module |
| `receipt` | `show_logo` | boolean | `false` | POS/Receipt module |
| `inventory` | `low_stock_default_threshold` | integer | `5` | Inventory module |
| `inventory` | `expiry_alert_days` | integer | `30` | Inventory module |

**Note:** Payment methods (cash, credit_card, qr, transfer) are hardcoded and are **not** stored in settings.

## Requirements
- Display all settings organized by group in a tabbed or sectioned UI
- Admin can update any setting value via a single form per group (or all at once)
- Settings are validated per type (numeric for tax, boolean for show_logo, etc.)
- Settings are cached with a tagged cache; cache is flushed on any update
- All settings are seeded with sensible defaults
- Settings can be read by any module using a helper or service class
- Admin-only access (requires `settings.manage` permission)

## Acceptance Criteria
- [ ] Seeder populates all Phase 1 settings with defaults
- [ ] Admin can view all settings grouped by category (general, tax)
- [ ] Admin can update any setting and changes are persisted
- [ ] Tax rate accepts only numeric values (0–100)
- [ ] Cache is invalidated immediately after any setting update
- [ ] `Setting::get('key')` returns the cached value
- [ ] `Setting::set('key', 'value')` updates and flushes cache
- [ ] Non-admin users cannot access the settings page or API

## Dependencies
- Phase 1: Authentication (admin must be logged in)
- Phase 1: Roles & Permissions (`settings.manage` permission)
- Consumed by: POS (tax rate), Receipt (header, footer, show_logo, store info), Inventory (thresholds)

## Notes
- No UI for adding new setting keys — they are predefined and seeded
- The `name` column holds a human-readable label for display in the admin UI (e.g., `"Tax Rate (%)"`)
- The `value` column stores everything as a string; type casting is handled by the `Setting` model or helper
- `timezone` setting affects how timestamps are displayed, not how they are stored (always UTC in DB)
- Future groups (e.g., `notifications`) can be added by seeding new rows without schema changes
