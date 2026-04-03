# Settings — API

## Web Routes (Inertia)

These routes render Inertia pages or redirect after form submission.

### GET `/settings`
Returns the settings page as an Inertia response with all settings grouped.

**Inertia Props:**
```json
{
  "settings": {
    "general": {
      "business_name": "My Store",
      "business_address": "",
      "business_phone": "",
      "timezone": "UTC"
    },
    "tax": {
      "tax_rate": "0"
    }
  },
  "groups": ["general", "tax"]
}
```

---

### PUT `/settings/general`
Update general settings. Redirects back to `/settings` on success.

**Request Body:**
```json
{
  "business_name": "My Business",
  "business_address": "456 Commerce Blvd",
  "business_phone": "+1 555 000 1234",
  "timezone": "America/New_York"
}
```

**Validation Rules:**
| Field | Rules |
|---|---|
| `business_name` | required, string, max:100 |
| `business_address` | nullable, string, max:500 |
| `business_phone` | nullable, string, max:30 |
| `timezone` | required, string, must be valid PHP timezone |

**Response:** Redirect to `settings` with flash message.

---

### PUT `/settings/tax`
Update tax settings. Redirects back to `/settings` on success.

**Request Body:**
```json
{ "tax_rate": 8.5 }
```

**Validation Rules:**
| Field | Rules |
|---|---|
| `tax_rate` | required, numeric, min:0, max:100 |

**Response:** Redirect to `settings` with flash message.

---

## Middleware
- All settings routes require `auth` middleware
- Authorization enforced via `PermissionsEnum::SETTINGS_MANAGE` in controller
