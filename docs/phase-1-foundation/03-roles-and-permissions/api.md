# Roles & Permissions — API

## Endpoints

### GET `/roles`
List all roles with their assigned permissions.

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "admin",
      "permissions": ["users.manage", "stores.manage", "sales.create", "..."]
    },
    {
      "id": 2,
      "name": "sales_rep",
      "permissions": ["sales.create", "reports.view_own"]
    }
  ]
}
```

---

### GET `/permissions`
List all permissions grouped by category.

**Response (200):**
```json
{
  "data": {
    "Administration": [
      { "id": 1, "name": "users.manage", "category": "Administration" },
      { "id": 2, "name": "stores.manage", "category": "Administration" },
      { "id": 16, "name": "settings.manage", "category": "Administration" }
    ],
    "Sales": [
      { "id": 9, "name": "sales.create", "category": "Sales" },
      { "id": 10, "name": "sales.manage", "category": "Sales" },
      { "id": 11, "name": "sales.view_all", "category": "Sales" },
      { "id": 12, "name": "customers.manage", "category": "Sales" },
      { "id": 13, "name": "refunds.manage", "category": "Sales" }
    ],
    "Inventory": [
      { "id": 3, "name": "products.manage", "category": "Inventory" },
      { "id": 4, "name": "vendors.manage", "category": "Inventory" },
      { "id": 8, "name": "stock.adjust", "category": "Inventory" }
    ],
    "Purchasing": [
      { "id": 5, "name": "purchase_orders.create", "category": "Purchasing" },
      { "id": 6, "name": "purchase_orders.approve", "category": "Purchasing" },
      { "id": 7, "name": "reception_orders.manage", "category": "Purchasing" }
    ],
    "Reports": [
      { "id": 14, "name": "reports.view_own", "category": "Reports" },
      { "id": 15, "name": "reports.view_all", "category": "Reports" }
    ]
  }
}
```

---

### GET `/roles/{role}/permissions`
Get the current permission list for a specific role.

**URL Parameters:** `role` — role ID or name

**Response (200):**
```json
{
  "role": { "id": 2, "name": "sales_rep" },
  "permissions": ["sales.create", "reports.view_own"]
}
```

---

### PUT `/roles/{role}/permissions`
Replace the full set of permissions for a role. Admin-only. Cannot modify the `admin` role via this endpoint.

**Request Body:**
```json
{
  "permissions": [
    "sales.create",
    "sales.manage",
    "customers.manage",
    "reports.view_own"
  ]
}
```

**Response (200):**
```json
{
  "role": { "id": 2, "name": "sales_rep" },
  "permissions": ["sales.create", "sales.manage", "customers.manage", "reports.view_own"],
  "message": "Permissions updated successfully."
}
```

**Error (403):** Attempting to modify the `admin` role.
```json
{ "message": "The admin role permissions cannot be modified." }
```

---

## Inertia Page Props

The permissions configuration page is served as an Inertia response, not a pure JSON API:

### GET `/settings/permissions` (Inertia)
Returns the permissions management page with all data pre-loaded.

**Inertia Props:**
```json
{
  "roles": [
    { "id": 1, "name": "admin" },
    { "id": 2, "name": "sales_rep" }
  ],
  "allPermissions": { "Administration": [...], "Sales": [...], "..." },
  "salesRepPermissions": ["sales.create", "reports.view_own"]
}
```

---

## Middleware
- `GET /roles` — requires `auth` + `can:settings.manage`
- `GET /permissions` — requires `auth` + `can:settings.manage`
- `GET /roles/{role}/permissions` — requires `auth` + `can:settings.manage`
- `PUT /roles/{role}/permissions` — requires `auth` + `can:settings.manage`
- `GET /settings/permissions` (Inertia) — requires `auth` + `can:settings.manage`
