# Settings — API

## Endpoints

### GET `/settings`
Returns the settings page as an Inertia response with all settings grouped.

**Inertia Props:**
```json
{
  "settings": {
    "general": {
      "store_name": "My Store",
      "store_address": "123 Main St",
      "store_phone": "+1 234 567 8900",
      "timezone": "America/New_York"
    },
    "tax": {
      "tax_rate": "10.5"
    },
    "receipt": {
      "receipt_header": "Welcome to My Store!",
      "receipt_footer": "Thank you for shopping with us.",
      "show_logo": "true"
    },
    "inventory": {
      "low_stock_default_threshold": "5",
      "expiry_alert_days": "30"
    }
  },
  "groups": ["general", "tax", "receipt", "inventory"]
}
```

---

### PUT `/settings`
Update one or more settings in a single request. All keys in the request body are validated and updated.

**Request Body:**
```json
{
  "store_name": "New Store Name",
  "store_address": "456 New Street",
  "store_phone": "+1 987 654 3210",
  "timezone": "America/Los_Angeles"
}
```

**Response (200):**
```json
{
  "message": "Settings updated successfully.",
  "settings": {
    "store_name": "New Store Name",
    "store_address": "456 New Street",
    "store_phone": "+1 987 654 3210",
    "timezone": "America/Los_Angeles"
  }
}
```

---

### PUT `/settings/general`
Update only the `general` group settings.

**Request Body:**
```json
{
  "store_name": "My Updated Store",
  "store_address": "789 Commerce Blvd",
  "store_phone": "+1 555 000 1234",
  "timezone": "UTC"
}
```

**Response (200):** Same as `PUT /settings` scoped to the group.

---

### PUT `/settings/tax`
Update tax settings.

**Request Body:**
```json
{ "tax_rate": "8.5" }
```

**Response (200):**
```json
{ "message": "Settings updated successfully.", "settings": { "tax_rate": "8.5" } }
```

---

### PUT `/settings/receipt`
Update receipt settings.

**Request Body:**
```json
{
  "receipt_header": "Welcome to Our Store!",
  "receipt_footer": "No refunds after 7 days.",
  "show_logo": true
}
```

**Response (200):**
```json
{
  "message": "Settings updated successfully.",
  "settings": {
    "receipt_header": "Welcome to Our Store!",
    "receipt_footer": "No refunds after 7 days.",
    "show_logo": "true"
  }
}
```

---

### PUT `/settings/inventory`
Update inventory threshold settings.

**Request Body:**
```json
{
  "low_stock_default_threshold": 10,
  "expiry_alert_days": 14
}
```

**Response (200):**
```json
{
  "message": "Settings updated successfully.",
  "settings": {
    "low_stock_default_threshold": "10",
    "expiry_alert_days": "14"
  }
}
```

---

### GET `/settings/public`
Returns a minimal public subset of settings (no auth required). Used by frontend for display purposes (e.g., store name in navbar).

**Response (200):**
```json
{
  "store_name": "My Store",
  "timezone": "UTC"
}
```

---

## Middleware
- `GET /settings` — requires `auth` + `can:settings.manage`
- `PUT /settings` — requires `auth` + `can:settings.manage`
- `PUT /settings/{group}` — requires `auth` + `can:settings.manage`
- `GET /settings/public` — no auth required (public route, filtered keys only)
