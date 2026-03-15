# Roles & Permissions — Backend

## Implementation Steps

### 1. Install & Configure Spatie Laravel Permission

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Ensure the `User` model uses the `HasRoles` trait:

```php
// app/Models/User.php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    // ...
}
```

---

### 2. Migration — Add `category` to `permissions` Table

```
database/migrations/xxxx_add_category_to_permissions_table.php
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
};
```

---

### 3. Seeder

```
database/seeders/RolesAndPermissionsSeeder.php
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Administration
            ['name' => 'users.manage',    'category' => 'Administration'],
            ['name' => 'stores.manage',   'category' => 'Administration'],
            ['name' => 'settings.manage', 'category' => 'Administration'],

            // Inventory
            ['name' => 'products.manage', 'category' => 'Inventory'],
            ['name' => 'vendors.manage',  'category' => 'Inventory'],
            ['name' => 'stock.adjust',    'category' => 'Inventory'],

            // Purchasing
            ['name' => 'purchase_orders.create',   'category' => 'Purchasing'],
            ['name' => 'purchase_orders.approve',  'category' => 'Purchasing'],
            ['name' => 'reception_orders.manage',  'category' => 'Purchasing'],

            // Sales
            ['name' => 'sales.create',     'category' => 'Sales'],
            ['name' => 'sales.manage',     'category' => 'Sales'],
            ['name' => 'sales.view_all',   'category' => 'Sales'],
            ['name' => 'customers.manage', 'category' => 'Sales'],
            ['name' => 'refunds.manage',   'category' => 'Sales'],

            // Reports
            ['name' => 'reports.view_own', 'category' => 'Reports'],
            ['name' => 'reports.view_all', 'category' => 'Reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['category' => $permission['category']]
            );
        }

        // Create roles
        $admin    = Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
        $salesRep = Role::firstOrCreate(['name' => 'sales_rep', 'guard_name' => 'web']);

        // Admin gets all permissions
        $admin->syncPermissions(Permission::all());

        // Sales Rep gets minimal default permissions
        $salesRep->syncPermissions([
            'sales.create',
            'reports.view_own',
        ]);
    }
}
```

Register in `DatabaseSeeder`:
```php
$this->call(RolesAndPermissionsSeeder::class);
```

---

### 4. Controller

```
app/Http/Controllers/RolePermissionController.php
```

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(
        private readonly RolePermissionService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/Permissions', [
            'roles'               => Role::all(['id', 'name']),
            'allPermissions'      => Permission::all()->groupBy('category'),
            'salesRepPermissions' => Role::findByName('sales_rep')
                                        ->permissions
                                        ->pluck('name'),
        ]);
    }

    public function rolePermissions(Role $role): JsonResponse
    {
        return response()->json([
            'role'        => $role->only(['id', 'name']),
            'permissions' => $role->permissions->pluck('name'),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $this->service->syncPermissions($role, $request->validated('permissions'));

        return response()->json([
            'role'        => $role->only(['id', 'name']),
            'permissions' => $role->fresh()->permissions->pluck('name'),
            'message'     => 'Permissions updated successfully.',
        ]);
    }
}
```

---

### 5. Service Class

```
app/Services/RolePermissionService.php
```

```php
<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionService
{
    /**
     * Sync permissions for a role.
     * The admin role cannot be modified via this service.
     */
    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        if ($role->name === 'admin') {
            abort(403, 'The admin role permissions cannot be modified.');
        }

        $role->syncPermissions($permissionNames);

        // Flush Spatie's permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        activity('permissions')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties(['permissions' => $permissionNames])
            ->log('permissions_synced');

        return $role->fresh(['permissions']);
    }
}
```

---

### 6. Form Request

```
app/Http/Requests/UpdateRolePermissionsRequest.php
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Permission;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    public function rules(): array
    {
        $validPermissions = Permission::pluck('name')->toArray();

        return [
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', $validPermissions)],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.*.in' => 'One or more permission names are invalid.',
        ];
    }
}
```

---

### 7. Policy

```
app/Policies/RolePolicy.php
```

```php
<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, Role $role): bool
    {
        // Admin role is immutable via this policy
        return $user->can('settings.manage') && $role->name !== 'admin';
    }
}
```

Register in `AuthServiceProvider` (or `AppServiceProvider` in Laravel 12):
```php
Gate::policy(Role::class, RolePolicy::class);
```

---

### 8. Routes

```php
// routes/web.php
use App\Http\Controllers\RolePermissionController;

Route::middleware(['auth', 'can:settings.manage'])->group(function () {
    Route::get('/settings/permissions', [RolePermissionController::class, 'index'])
         ->name('settings.permissions');

    Route::get('/roles/{role}/permissions', [RolePermissionController::class, 'rolePermissions'])
         ->name('roles.permissions');

    Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'update'])
         ->name('roles.permissions.update');
});
```

---

### 9. Inertia Shared Props — Permissions

Share user permissions on every request via `HandleInertiaRequests`:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user() ? [
                'id'          => $request->user()->id,
                'name'        => $request->user()->full_name,
                'email'       => $request->user()->email,
                'roles'       => $request->user()->getRoleNames(),
                'permissions' => $request->user()->getAllPermissions()->pluck('name'),
            ] : null,
        ],
    ]);
}
```

---

### 10. Activity Logging

Log all permission sync events:

```php
activity('permissions')
    ->performedOn($role)
    ->causedBy(auth()->user())
    ->withProperties([
        'role'        => $role->name,
        'permissions' => $permissionNames,
    ])
    ->log('permissions_synced');
```

Log events: `permissions_synced`

---

## Good Practices
- Always call `app()[PermissionRegistrar::class]->forgetCachedPermissions()` after any `syncPermissions()` or `givePermissionTo()` call
- Never allow the `admin` role to be modified via the API — enforce this in both the service and policy
- Eager load `permissions` on the `Role` model when returning lists to avoid N+1 queries
- Use `$user->can('permission.name')` consistently — do not mix with `$user->hasPermissionTo()` in controllers
- Return the `sales_rep` role's permissions from Inertia props, not a separate API call, to reduce round trips
