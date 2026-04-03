# Roles & Permissions — Database

## Tables Used

### `roles` (Spatie)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(125) | UNIQUE per guard — e.g., `admin`, `sales_rep` |
| `guard_name` | VARCHAR(125) | Default: `web` |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### `permissions` (Spatie + custom `category` column)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(125) | UNIQUE per guard — e.g., `sales.create` |
| `guard_name` | VARCHAR(125) | Default: `web` |
| `category` | VARCHAR(50) | Custom column — e.g., `Sales`, `Inventory`, `Administration` |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### `role_has_permissions` (Spatie pivot)
| Column | Type | Notes |
|---|---|---|
| `permission_id` | BIGINT UNSIGNED | FK → permissions.id CASCADE |
| `role_id` | BIGINT UNSIGNED | FK → roles.id CASCADE |

### `model_has_roles` (Spatie pivot)
| Column | Type | Notes |
|---|---|---|
| `role_id` | BIGINT UNSIGNED | FK → roles.id CASCADE |
| `model_type` | VARCHAR(125) | Polymorphic type — `App\Models\User` |
| `model_id` | BIGINT UNSIGNED | FK → users.id |

### `model_has_permissions` (Spatie pivot)
| Column | Type | Notes |
|---|---|---|
| `permission_id` | BIGINT UNSIGNED | FK → permissions.id CASCADE |
| `model_type` | VARCHAR(125) | Polymorphic type |
| `model_id` | BIGINT UNSIGNED | |

### `store_user` (custom pivot — defined in User Management)
| Column | Type | Notes |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `store_id` | BIGINT UNSIGNED | FK → stores.id CASCADE |
| `user_id` | BIGINT UNSIGNED | FK → users.id CASCADE |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

## Indexes
- `roles.name` + `roles.guard_name` — UNIQUE composite
- `permissions.name` + `permissions.guard_name` — UNIQUE composite
- `model_has_roles(model_id, model_type)` — INDEX (Spatie default)
- `store_user(store_id, user_id)` — UNIQUE composite (one assignment per user per store)

## Migration Notes

### Add `category` column to permissions table
The Spatie default migration does not include a `category` column. Add it via a separate migration:

```php
// database/migrations/xxxx_add_category_to_permissions_table.php
public function up(): void
{
    Schema::table('permissions', function (Blueprint $table) {
        $table->string('category', 50)->default('General')->after('guard_name');
    });
}

public function down(): void
{
    Schema::table('permissions', function (Blueprint $table) {
        $table->dropColumn('category');
    });
}
```

## Query Patterns

```php
// Get all permissions grouped by category
Permission::all()->groupBy('category');

// Get permissions for the sales_rep role
Role::findByName('sales_rep')->permissions()->pluck('name');

// Check if a user has a permission
$user->can('sales.create');

// Get all permissions a user has (direct + via roles)
$user->getAllPermissions()->pluck('name');

// Sync permissions on the sales_rep role
$role = Role::findByName('sales_rep');
$role->syncPermissions($permissionNames);
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

## Relationships

```php
// User model (via Spatie HasRoles trait)
$user->roles;               // BelongsToMany Role
$user->permissions;         // BelongsToMany Permission (direct)
$user->getAllPermissions();  // Collection of all permissions (role + direct)

// Role model
$role->permissions;         // BelongsToMany Permission

// store_user relationship on User
public function stores(): BelongsToMany
{
    return $this->belongsToMany(Store::class)
        ->withTimestamps();
}
```
