# Store Management — Database

## Tables Used

### `stores`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(100) | Required — display name |
| `code` | VARCHAR(20) | UNIQUE, required — short identifier (e.g., `HQ`) |
| `address` | TEXT | NULLABLE — full street address |
| `status` | ENUM('active','inactive') | Default: `active` |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### `store_user` (pivot)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `store_id` | BIGINT UNSIGNED | FK → stores.id CASCADE DELETE |
| `user_id` | BIGINT UNSIGNED | FK → users.id CASCADE DELETE |
| `role_id` | BIGINT UNSIGNED | FK → roles.id — role within this store |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### `media` (Spatie Media Library)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `model_type` | VARCHAR(255) | Polymorphic — `App\Models\Store` |
| `model_id` | BIGINT UNSIGNED | FK → stores.id |
| `collection_name` | VARCHAR(255) | `logo` for store logos |
| `name` | VARCHAR(255) | Original filename |
| `file_name` | VARCHAR(255) | Stored filename |
| `mime_type` | VARCHAR(255) | e.g., `image/jpeg` |
| `disk` | VARCHAR(255) | `public` or `s3` |
| `size` | BIGINT UNSIGNED | File size in bytes |
| `uuid` | UUID | UNIQUE |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

## Indexes
- `stores.code` — UNIQUE
- `stores.status` — INDEX (for filtering)
- `store_user(store_id, user_id)` — UNIQUE composite (prevent duplicate assignments)
- `media(model_type, model_id)` — INDEX (Spatie default)

## Migrations
- `xxxx_create_stores_table.php` — columns as defined in table above, with `unique('code')` and `index('status')`
- `xxxx_create_store_user_table.php` — pivot with `store_id`, `user_id`, `role_id` FKs, `unique(['store_id', 'user_id'])`

## Relationships
- `Store ↔ User`: `BelongsToMany` via `store_user` pivot with `role_id`, both sides use `withPivot('role_id')->withTimestamps()`

## Query Hints
- List stores: `->withCount('users')`, filter by `status`, search `name` or `code` with `LIKE`
- Store detail: eager load `users.roles` to avoid N+1
- Use `when()` for conditional query building per service conventions

## Media Library Notes
- Collection name: `logo`
- Single file per store (use `addMediaCollection('logo')->singleFile()`)
- Conversions: generate a `thumb` (100x100) for list views
- Stored on `public` disk by default; configure `s3` for production
