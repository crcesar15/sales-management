# User Management — API

## Endpoints

### GET `/users`
List all users with pagination, search, and filters.

**Query Parameters:**
```
?search=john          // search by name or email
?status=active        // filter by status
?store_id=1           // filter by store
?page=1               // pagination
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "username": "johndoe",
      "phone": "+1234567890",
      "status": "active",
      "roles": ["admin"],
      "stores": [{ "id": 1, "name": "Main Store" }],
      "created_at": "2026-01-01T00:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 45 }
}
```

---

### POST `/users`
Create a new user.

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com",
  "username": "janesmith",
  "phone": "+1234567890",
  "password": "temporarypass",
  "password_confirmation": "temporarypass",
  "status": "active",
  "role": "sales_rep",
  "store_ids": [1, 2]
}
```

**Response (201):** Created user object.

---

### GET `/users/{id}`
Get a single user's details.

**Response (200):** Full user object including stores and roles.

---

### PUT `/users/{id}`
Update a user's profile.

**Request Body:** Same as POST, all fields optional.

**Response (200):** Updated user object.

---

### PATCH `/users/{id}/status`
Change a user's status.

**Request Body:**
```json
{ "status": "inactive" }
```

**Response (200):** Updated user object.

---

### DELETE `/users/{id}`
Soft-delete a user.

**Response (204):** No content.

---

### POST `/users/{id}/stores`
Assign a user to a store.

**Request Body:**
```json
{ "store_id": 1 }
```

**Response (200):** Updated user object.

---

### DELETE `/users/{id}/stores/{store_id}`
Remove a user from a store.

**Response (204):** No content.

---

## Middleware
All routes require `auth` + `can:users.manage` permission.
