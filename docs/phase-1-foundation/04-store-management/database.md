# Store Management — Database

## Tables Used

### `stores`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(100) | Required — display name |
| `code` | VARCHAR(20) | UNIQUE, required — short identifier (e.g., `HQ`) |
| `address` | VARCHAR(255) | NULLABLE — street address |
| `city` | VARCHAR(100) | NULLABLE |
| `state` | VARCHAR(100) | NULLABLE |
| `zip_code` | VARCHAR(20) | NULLABLE |
| `phone` | VARCHAR(30) | NULLABLE — store contact phone |
| `email` | VARCHAR(150) | NULLABLE — store contact email |
| `status` | ENUM('active','inactive') | Default: `active` |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | NULLABLE — soft deletes |

### `store_user` (pivot)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `store_id` | BIGINT UNSIGNED | FK → stores.id CASCADE DELETE |
| `user_id` | BIGINT UNSIGNED | FK → users.id CASCADE DELETE |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

## Indexes
- `stores.code` — UNIQUE
- `stores.status` — INDEX (for filtering)
- `stores.deleted_at` — INDEX (for soft delete queries)
- `store_user(store_id, user_id)` — UNIQUE composite (prevent duplicate assignments)

## Migrations
- `xxxx_create_stores_table.php` — columns as defined in table above, with `unique('code')`, `index('status')`, `index('deleted_at')`, and `softDeletes()`
- `xxxx_create_store_user_table.php` — pivot with `store_id`, `user_id` FKs, `unique(['store_id', 'user_id'])`

## Relationships
- `Store ↔ User`: `BelongsToMany` via `store_user` pivot, both sides use `withTimestamps()`

## Query Hints
- List stores: `->withCount('users')`, filter by `status`, search `name` or `code` with `LIKE`
- Store detail: eager load `users.roles` to avoid N+1
- Use `when()` for conditional query building per service conventions
- Include trashed stores with `withTrashed()` when restoring or viewing deleted stores
