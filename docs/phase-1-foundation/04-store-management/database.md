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

## Migration — `stores` Table

```php
// database/migrations/xxxx_create_stores_table.php
public function up(): void
{
    Schema::create('stores', function (Blueprint $table) {
        $table->id();
        $table->string('name', 100);
        $table->string('code', 20)->unique();
        $table->text('address')->nullable();
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->timestamps();

        $table->index('status');
    });
}
```

## Migration — `store_user` Pivot

```php
// database/migrations/xxxx_create_store_user_table.php
public function up(): void
{
    Schema::create('store_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('store_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('role_id')->constrained('roles');
        $table->timestamps();

        $table->unique(['store_id', 'user_id']);
    });
}
```

## Query Patterns

```php
// List active stores with user count
Store::where('status', 'active')
    ->withCount('users')
    ->orderBy('name')
    ->paginate(20);

// Search stores by name or code
Store::where(function ($q) use ($search) {
    $q->where('name', 'like', "%{$search}%")
      ->orWhere('code', 'like', "%{$search}%");
})->paginate(20);

// Get all users for a store with their role
Store::with(['users' => fn ($q) => $q->withPivot('role_id')])
    ->findOrFail($storeId);

// Get users with role names (join roles table)
$store->users()
    ->withPivot('role_id')
    ->with('roles')
    ->get();
```

## Relationships

```php
// Store model
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class)
        ->withPivot('role_id')
        ->withTimestamps();
}

// User model (inverse)
public function stores(): BelongsToMany
{
    return $this->belongsToMany(Store::class)
        ->withPivot('role_id')
        ->withTimestamps();
}
```

## Media Library Notes
- Collection name: `logo`
- Single file per store (use `addMediaCollection('logo')->singleFile()`)
- Conversions: generate a `thumb` (100x100) for list views
- Stored on `public` disk by default; configure `s3` for production
