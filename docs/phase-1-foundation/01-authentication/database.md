# Authentication — Database

## Existing Tables Used
No new tables are required. The following existing tables are used:

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `first_name` | VARCHAR(50) | |
| `last_name` | VARCHAR(50) | |
| `email` | VARCHAR(100) | UNIQUE — used for login |
| `username` | VARCHAR(50) | UNIQUE — alternative login |
| `password` | VARCHAR | bcrypt hashed |
| `status` | ENUM('active','inactive','archived') | Inactive/archived users cannot log in |
| `email_verified_at` | TIMESTAMP | NULLABLE |
| `remember_token` | VARCHAR(100) | NULLABLE |
| `deleted_at` | TIMESTAMP | NULLABLE (soft deletes) |

### `password_reset_tokens`
| Column | Type | Notes |
|---|---|---|
| `email` | VARCHAR | PRIMARY KEY |
| `token` | VARCHAR | Hashed token |
| `created_at` | TIMESTAMP | Used to check expiry (60 min) |

### `personal_access_tokens`
Not used for web authentication. Session-based auth only.

## Indexes
- `users.email` — UNIQUE index (already exists)
- `users.username` — UNIQUE index (already exists)
- `users.status` — INDEX (already exists, used for login gate check)

## Notes
- No migrations needed for this task — all tables already exist
- Ensure `users.status` is checked during authentication (block inactive/archived users)
