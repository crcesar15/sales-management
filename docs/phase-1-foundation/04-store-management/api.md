# Store Management — API

> **Status: Deferred.** This API spec is kept for a future REST API phase. The v1 implementation uses Inertia routes exclusively — see `backend.md` and `frontend.md` for the current approach. No separate `Api/StoreController` is needed at this time.

## Endpoints

### GET `/stores`
List all stores with pagination and optional search.

**Query Parameters:**
```
?search=main     // search by name or code
?status=active   // filter by status
?page=1          // pagination
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Main Store",
      "code": "HQ",
      "address": "123 Main St",
      "city": "Springfield",
      "state": "Illinois",
      "zip_code": "62701",
      "phone": "+1 555-0100",
      "email": "main@store.com",
      "status": "active",
      "users_count": 5,
      "created_at": "2026-01-01T00:00:00Z",
      "updated_at": "2026-01-01T00:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

---

### POST `/stores`
Create a new store.

**Request Body (JSON):**
```json
{
  "name": "Branch Store",
  "code": "BR1",
  "address": "456 Branch Ave",
  "city": "Shelbyville",
  "state": "Illinois",
  "zip_code": "62565",
  "phone": "+1 555-0200",
  "email": "branch@store.com",
  "status": "active"
}
```

**Response (201):** Created store object.

---

### GET `/stores/{id}`
Get a single store with its assigned users.

**Response (200):**
```json
{
  "id": 1,
  "name": "Main Store",
  "code": "HQ",
  "address": "123 Main St",
  "city": "Springfield",
  "state": "Illinois",
  "zip_code": "62701",
  "phone": "+1 555-0100",
  "email": "main@store.com",
  "status": "active",
  "users": [
    {
      "id": 1,
      "full_name": "John Doe",
      "email": "john@example.com"
    },
    {
      "id": 2,
      "full_name": "Jane Smith",
      "email": "jane@example.com"
    }
  ],
  "created_at": "2026-01-01T00:00:00Z",
  "updated_at": "2026-01-01T00:00:00Z",
  "deleted_at": null
}
```

---

### PUT `/stores/{id}`
Update store details.

**Request Body (JSON):**
```json
{
  "name": "Updated Store Name",
  "code": "HQ2",
  "address": "789 New St",
  "city": "Springfield",
  "state": "Illinois",
  "zip_code": "62702",
  "phone": "+1 555-0300",
  "email": "updated@store.com",
  "status": "active"
}
```

**Response (200):** Updated store object.

---

### DELETE `/stores/{id}`
Soft-delete a store.

**Response (200):**
```json
{ "message": "Store deleted successfully." }
```

---

### PATCH `/stores/{id}/restore`
Restore a soft-deleted store.

**Response (200):**
```json
{ "message": "Store restored successfully." }
```

---

### PATCH `/stores/{id}/status`
Toggle store active/inactive status.

**Request Body:**
```json
{ "status": "inactive" }
```

**Response (200):** Updated store object.

---

### POST `/stores/{id}/users`
Assign a user to a store.

**Request Body:**
```json
{
  "user_id": 3
}
```

**Response (200):**
```json
{
  "message": "User assigned to store successfully.",
  "user": { "id": 3, "full_name": "Bob Lee" }
}
```

**Error (409):** User is already assigned to this store.
```json
{ "message": "This user is already assigned to this store." }
```

---

### DELETE `/stores/{id}/users/{user_id}`
Remove a user from a store.

**Response (204):** No content.

---

## Middleware
All routes require `auth` + `can:stores.manage` permission.
