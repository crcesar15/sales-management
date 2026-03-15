# User Management — Database

## Tables Used

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `first_name` | VARCHAR(50) | Required |
| `last_name` | VARCHAR(50) | Required |
| `email` | VARCHAR(100) | UNIQUE, required |
| `username` | VARCHAR(50) | UNIQUE, required |
| `phone` | VARCHAR(20) | NULLABLE |
| `status` | ENUM('active','inactive','archived') | Default: active |
| `date_of_birth` | DATE | NULLABLE |
| `additional_properties` | JSON | NULLABLE — flexible metadata |
| `email_verified_at` | TIMESTAMP | NULLABLE |
| `password` | VARCHAR | bcrypt hashed |
| `remember_token` | VARCHAR(100) | NULLABLE |
| `deleted_at` | TIMESTAMP | NULLABLE — soft deletes |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### `store_user` (pivot)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `store_id` | BIGINT UNSIGNED | FK → stores.id CASCADE |
| `user_id` | BIGINT UNSIGNED | FK → users.id CASCADE |
| `role_id` | BIGINT UNSIGNED | FK → roles.id CASCADE |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

## Indexes
- `users.email` — UNIQUE
- `users.username` — UNIQUE
- `users.status` — INDEX (for filtering)
- `users.deleted_at` — used by SoftDeletes scope

## No New Migrations Required
All tables already exist. No schema changes needed for this task.

## Query Patterns
```php
// Active users for a specific store
User::whereHas('stores', fn($q) => $q->where('store_id', $storeId))
    ->where('status', 'active')
    ->get();

// Search users by name or email
User::where('first_name', 'like', "%{$search}%")
    ->orWhere('last_name', 'like', "%{$search}%")
    ->orWhere('email', 'like', "%{$search}%")
    ->paginate(20);
```

## Relationships
```php
// User model
public function stores(): BelongsToMany
{
    return $this->belongsToMany(Store::class)
        ->withPivot('role_id')
        ->withTimestamps();
}
```
