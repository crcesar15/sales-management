# Authentication — API

## Endpoints

### POST `/login`
Authenticate a user with email/username and password.

**Request Body:**
```json
{
  "login": "admin@example.com",   // email OR username
  "password": "secret",
  "remember": true                 // optional
}
```

**Success Response:** Inertia redirect to role-based route (no JSON body for web).

**Error Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "login": ["The provided credentials are incorrect."]
  }
}
```

**Error Response (429 — Rate Limited):**
```json
{
  "message": "Too many login attempts. Please try again in 60 seconds."
}
```

---

### POST `/logout`
Invalidate the current session and redirect to login.

**Request Body:** None (uses CSRF token).

**Success Response:** Inertia redirect to `/login`.

---

### POST `/forgot-password`
Send a password reset link to the given email address.

**Request Body:**
```json
{
  "email": "admin@example.com"
}
```

**Success Response (200):**
```json
{
  "message": "We have emailed your password reset link."
}
```

**Note:** Always return success to avoid email enumeration.

---

### POST `/reset-password`
Reset the user's password using a valid token.

**Request Body:**
```json
{
  "token": "abc123...",
  "email": "admin@example.com",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Success Response:** Inertia redirect to `/login` with success flash message.

**Error Response (422):**
```json
{
  "message": "This password reset token is invalid."
}
```

---

## Middleware
| Route | Middleware |
|---|---|
| `/login` | `guest` |
| `/logout` | `auth` |
| `/forgot-password` | `guest` |
| `/reset-password` | `guest` |

## Rate Limiting
- Login endpoint: max 5 attempts per minute per IP + email combination
- Uses Laravel's built-in `RateLimiter` via `ThrottleRequests` middleware
