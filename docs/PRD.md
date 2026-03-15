# Product Requirements Document (PRD)
# Sales Management System

**Version:** 1.0
**Date:** March 15, 2026
**Status:** Active

---

## 1. Executive Summary

### Problem Statement

Retail businesses operating across one or more physical stores lack a unified system to track inventory movements, manage sales, control purchasing, and gain operational visibility. Without a structured system, stock discrepancies, untracked sales, and manual processes lead to revenue loss and operational inefficiency.

### Proposed Solution

A production-grade, web-based Sales Management System built for retail stores (with future storehouse support) that unifies inventory tracking, point-of-sale operations, purchasing workflows, and reporting under a single platform. The system is accessible on both desktop and tablet, enforces role-based permissions, and tracks every critical inventory and sales event with a full audit trail.

### Success Criteria

1. **Inventory Accuracy**: Stock discrepancy rate < 2% after 30 days of operation.
2. **POS Performance**: A sales rep can complete a sale (product search → cart → payment → receipt) in under 60 seconds.
3. **Purchase Cycle**: Time from purchase order creation to stock increment (after reception) is fully tracked with zero manual reconciliation.
4. **User Adoption**: All 4 launch users can operate their respective modules without training after reading the documentation.
5. **Data Integrity**: 100% of critical actions (stock adjustments, sales, refunds, permission changes) are recorded in the activity log.

---

## 2. User Experience & Functionality

### 2.1 User Personas

#### Admin
- **Who**: Store owner or operations manager.
- **Goals**: Full visibility and control over all stores, users, products, purchasing, inventory, and reports.
- **Needs**: Dashboard with KPIs, ability to manage all entities, approve purchase orders, configure settings.
- **Frustrations**: Lack of visibility into stock levels, manual reconciliation, no audit trail.

#### Sales Rep
- **Who**: Front-line staff working at the point of sale.
- **Goals**: Quickly process sales, check stock availability, manage customer information.
- **Needs**: Fast POS interface, access to their own sales history, ability to search products by name/SKU/barcode.
- **Frustrations**: Slow checkout process, inability to find products quickly, no receipt generation.

---

### 2.2 User Stories & Acceptance Criteria

#### Authentication
- **As a user**, I want to log in with my email/username and password so that I can access the system securely.
  - Login works with both email and username.
  - Invalid credentials display a specific error message.
  - User is redirected based on role after login (Admin → Dashboard, Sales Rep → POS).
  - Sessions expire after a configurable period of inactivity.

#### User Management
- **As an Admin**, I want to create and manage users so that I can control who has access to the system.
  - Admin can create, edit, deactivate, and soft-delete users.
  - Inactive users cannot log in and receive a clear message.
  - Users can be assigned to one or more stores.

#### Roles & Permissions
- **As an Admin**, I want to assign granular permissions to roles so that each user only accesses what they need.
  - Two base roles: Admin and Sales Rep.
  - Permissions are enforced on both frontend (UI visibility) and backend (API authorization).
  - Unauthorized access returns HTTP 403.

#### Store Management
- **As an Admin**, I want to create and manage stores so that I can operate across multiple locations.
  - Admin can create, edit, and deactivate stores.
  - Users are assigned to stores with a specific role.
  - Store logo can be uploaded and appears on receipts.

#### Product Catalog
- **As an Admin**, I want to manage products, variants, and options so that the catalog is always accurate.
  - Products have a name, description, brand, measurement unit, categories, status, and media.
  - Variants are defined by combinations of options (e.g., Color + Size).
  - Variants can be auto-generated from option combinations or manually created.
  - Each variant has a SKU/barcode, base price, and base unit.

#### Unit Conversion (Purchase vs Sale)
- **As an Admin**, I want to configure how products are purchased vs sold so that stock is always tracked in the correct base unit.
  - A product variant has one base (sale) unit.
  - Additional sale units can be configured with a conversion factor and price (e.g., Box of 12 = 12 units at $10).
  - Vendor catalog entries define the purchase unit and conversion factor per vendor.
  - On reception, stock is always incremented in base units (quantity × conversion factor).

#### Inventory Management
- **As an Admin**, I want to see stock levels per store and globally so that I can make informed purchasing decisions.
  - Stock overview shows current stock per variant, per store, and as a global total.
  - Low stock alerts are triggered when stock falls below a configurable threshold.
  - Expiry alerts are triggered for batches approaching their expiry date.

#### Stock Adjustments
- **As a user with the `stock.adjust` permission**, I want to adjust stock levels with a reason so that discrepancies are documented.
  - Adjustment reasons: physical audit, robbery, expiration, damage, other.
  - Every adjustment is logged in the activity log with the reason, quantity, and user.

#### Stock Transfers
- **As an Admin**, I want to transfer stock between stores so that inventory is balanced across locations.
  - Full workflow: request → pick → ship → receive → reconcile.
  - Transfer reduces stock at the source store and increments at the destination store.

#### Purchasing
- **As a user with `purchase_orders.create` permission**, I want to create purchase orders so that I can restock inventory.
  - Purchase orders can only include products present in the selected vendor's catalog.
  - Workflow: draft → awaiting approval → approved → sent → paid → cancelled.
  - A user with `purchase_orders.approve` permission must approve before the order is sent.

#### Reception
- **As a user with `reception_orders.manage` permission**, I want to receive goods against a purchase order so that stock is updated.
  - Multiple partial receptions are supported per purchase order.
  - On reception completion, batches are auto-generated and stock is incremented in base units.
  - Each reception specifies the destination store.

#### Point of Sale
- **As a Sales Rep**, I want to process a sale quickly using the POS interface so that customers are served efficiently.
  - Products searchable by name, SKU, and barcode.
  - Cart supports multiple items, order-level discount, and tax calculation.
  - Payment methods: cash, credit card, QR, transfer.
  - Stock is deducted from the oldest batch first (FIFO) on sale completion.
  - POS supports selling in base unit or configured bulk sale units.

#### Receipts
- **As a Sales Rep**, I want to generate a receipt after a sale so that the customer has proof of purchase.
  - Digital receipt is shareable via link or email.
  - Print-ready receipt uses configurable header/footer from settings.
  - Receipt includes store info, items, quantities, prices, discount, tax, and total.

#### Refunds & Returns
- **As a user with `refunds.manage` permission**, I want to process a return against a completed sale so that inventory is restocked.
  - Returns reference the original sale order.
  - Returned items are restocked into inventory.
  - Return is logged in the activity log.

#### Reporting
- **As an Admin**, I want to view sales, inventory, and purchasing reports so that I can make data-driven decisions.
  - Dashboard shows KPIs: today's revenue, monthly sales, low stock count, pending POs, top 5 products.
  - Reports are filterable by store, date range, product, category, vendor, and sales rep.
  - Sales Rep can only see their own metrics unless they have `reports.view_all` permission.

---

### 2.3 Non-Goals (Out of Scope)

The following features are explicitly **not** in scope for this version:

- Multi-currency support
- Store-specific selling prices
- Per-product tax rates
- Configurable payment methods (hardcoded list)
- Real-time dashboard (WebSockets/SSE)
- Report exports (PDF, CSV, Excel)
- Loyalty / rewards program
- Product bundles or kits
- Barcode generation and printing
- Purchase order email to vendor
- Offline POS mode
- Mobile native app
- E-commerce or accounting integrations
- Multiple warehouse management
- Serial number tracking
- Consignment inventory
- Vendor performance tracking

---

## 3. Technical Specifications

### 3.1 Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 12 (PHP 8.3+) |
| Frontend Framework | Vue 3 (Composition API + `<script setup>`) |
| Server-Client Bridge | Inertia.js v2 |
| UI Component Library | PrimeVue |
| State Management | Pinia |
| CSS Framework | Tailwind CSS |
| Auth | Laravel Sanctum (session-based for web) |
| Permissions | Spatie Laravel Permission |
| Media Uploads | Spatie Laravel Media Library |
| Activity Logging | Spatie Laravel Activity Log |
| Route Helpers | Tightenco Ziggy |
| Testing | Pest 3 |
| Static Analysis | PHPStan / Larastan |
| Code Style | Laravel Pint |

### 3.2 Architecture Overview

```
Browser (Vue 3 + Inertia.js)
        │
        ▼
Laravel Router → Middleware (Auth, Permission)
        │
        ▼
Controller → Form Request (Validation)
        │
        ▼
Service Layer → Eloquent Models → Database (MySQL)
        │
        ▼
Inertia Response → Vue Page Component
```

- All frontend-backend interaction goes through **Inertia.js** with dedicated API endpoints where needed.
- Business logic is encapsulated in **Service classes** (not fat controllers).
- Authorization is handled by **Policies** backed by **Spatie permissions**.
- Media is handled by **Spatie Media Library** (products, store logo).
- All critical actions are logged via **Spatie Activity Log**.

### 3.3 Key Data Relationships

```
stores ──────────────── store_user (pivot: store_id, user_id, role_id)
                                │
users ──────────────────────────┘

products ──── product_variants ──── product_variant_option_values
    │               │
    │         product_options ──── product_option_values
    │
categories (many:many via category_product)

product_variants ──── sale_units (conversion factor, price)
product_variants ──── vendor_catalog (vendor, purchase_unit, conversion_factor, supply_price)

vendors ──── vendor_catalog ──── purchase_orders ──── purchase_order_product
                                        │
                                 reception_orders ──── reception_order_product
                                        │
                                      batches (store-scoped)

customers ──── sales_orders (store-scoped) ──── sales_order_product
                    │
                 refunds

batches ──── stock movements (FIFO on sale, restock on return, adjust on correction)
```

### 3.4 Permission Map

| Permission | Admin | Sales Rep |
|---|---|---|
| `users.manage` | ✓ | ✗ |
| `stores.manage` | ✓ | ✗ |
| `products.manage` | ✓ | ✗ |
| `vendors.manage` | ✓ | ✗ |
| `purchase_orders.create` | ✓ | configurable |
| `purchase_orders.approve` | ✓ | ✗ |
| `reception_orders.manage` | ✓ | configurable |
| `stock.adjust` | ✓ | configurable |
| `sales.create` | ✓ | ✓ |
| `sales.manage` | ✓ | configurable |
| `sales.view_all` | ✓ | ✗ |
| `customers.manage` | ✓ | configurable |
| `refunds.manage` | ✓ | configurable |
| `reports.view_own` | ✓ | ✓ |
| `reports.view_all` | ✓ | ✗ |
| `settings.manage` | ✓ | ✗ |

### 3.5 Integration Points

- **Database**: MySQL (via Laravel Eloquent ORM)
- **Authentication**: Laravel session-based auth with Sanctum
- **File Storage**: Local disk (configurable to S3 in future) via Spatie Media Library
- **Email**: Laravel Mail (for password reset and digital receipts)
- **Printing**: Browser `window.print()` for receipt printing (no thermal printer driver required)

### 3.6 Security & Privacy

- All routes protected by `auth` middleware.
- Permission checks enforced at both controller (Policy) and frontend (directive/computed) levels.
- Passwords hashed with bcrypt.
- Sensitive actions (stock adjustments, permission changes, refunds) logged with user ID and timestamp.
- Soft deletes used for: users, products, vendors, customers, stores, categories, brands, measurement units.
- No third-party data sharing or external API calls in v1.

---

## 4. Risks & Roadmap

### 4.1 Phased Rollout

#### Phase 1 — Foundation
Core infrastructure: authentication, user management, roles & permissions, store management, settings.

#### Phase 2 — Product Catalog
Categories, brands, measurement units, products, variants & options, sale units (unit conversion).

#### Phase 3 — Inventory Management
Stock overview (per store + global), batch tracking, stock transfers, stock adjustments, stock alerts.

#### Phase 4 — Purchasing
Vendor management, vendor catalog (with unit conversion), purchase orders (with approval workflow), reception orders (with batch auto-generation).

#### Phase 5 — POS & Sales
Customer management, POS interface (barcode + manual), sales orders, receipts (digital + print), refunds & returns.

#### Phase 6 — Reporting & Analytics
Dashboard (role-based KPIs + filters), sales reports, inventory reports, purchasing reports.

### 4.2 Technical Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| FIFO batch deduction logic errors | Medium | High | Comprehensive Pest tests for all batch consumption scenarios |
| Unit conversion errors (purchase → base unit) | Medium | High | Validate conversion on reception and add integration tests |
| Permission misconfiguration exposing data | Low | High | Policy-level tests for every sensitive endpoint |
| Stock desync between batches and variant stock | Medium | High | Use database transactions for all stock-mutating operations |
| POS performance on large catalogs (600+ products) | Low | Medium | Paginate product search, index SKU and name columns |
| Receipt printing inconsistency across browsers | Low | Low | Test print layout on Chrome, Safari, Firefox |

### 4.3 Dependencies Between Phases

```
Phase 1 (Foundation)
    └── Phase 2 (Product Catalog)
            └── Phase 3 (Inventory)
            └── Phase 4 (Purchasing) ──── Phase 3
                    └── Phase 5 (POS & Sales) ──── Phase 3
                            └── Phase 6 (Reporting)
```

No phase can be started until its dependency is complete.

---

## 5. Appendix

### 5.1 Glossary

| Term | Definition |
|---|---|
| **Base Unit** | The smallest unit used for inventory tracking and selling (e.g., individual can) |
| **Purchase Unit** | The unit in which a vendor sells the product (e.g., box of 12) |
| **Sale Unit** | A configured unit for selling that may differ from the base unit (e.g., 6-pack) |
| **Conversion Factor** | The multiplier from purchase/sale unit to base unit (e.g., 1 box = 12 units) |
| **Batch** | A specific lot of received stock with an optional expiry date and quantity tracking |
| **FIFO** | First In, First Out — oldest batches are consumed first during sales |
| **POS** | Point of Sale — the interface used by Sales Reps to process transactions |
| **Reception Order** | A record of goods physically received against a purchase order |
| **Stock Adjustment** | A manual correction to stock levels with a documented reason |
| **Stock Transfer** | Movement of stock from one store to another |

### 5.2 Launch Targets

| Metric | Launch Target | Long-term Target |
|---|---|---|
| Stores | 1 | Multiple |
| Users | 4 | Unlimited |
| Products | 300 | ~600 |
| Variants per product | ~3 | ~10 |
| Daily transactions | ~50 | ~500 |
