# Roles & Permissions — Testing

## Test File Location
```
tests/Feature/Permissions/RolePermissionTest.php
```

## Test Cases

```php
<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ─── Seeder ───────────────────────────────────────────────────────────────────

it('seeds all 16 permissions', function () {
    expect(Permission::count())->toBe(16);
});

it('seeds admin and sales_rep roles', function () {
    expect(Role::whereIn('name', ['admin', 'sales_rep'])->count())->toBe(2);
});

it('gives admin all permissions', function () {
    $admin = Role::findByName('admin');
    expect($admin->permissions->count())->toBe(16);
});

it('gives sales_rep only default permissions', function () {
    $salesRep = Role::findByName('sales_rep');
    $permissionNames = $salesRep->permissions->pluck('name')->sort()->values();

    expect($permissionNames->toArray())->toBe(['reports.view_own', 'sales.create']);
});

it('each permission has a category', function () {
    Permission::all()->each(function ($permission) {
        expect($permission->category)->not->toBeEmpty();
    });
});

// ─── Access Control ───────────────────────────────────────────────────────────

it('allows admin to access the permissions settings page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('settings.permissions'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Permissions')
            ->has('allPermissions')
            ->has('salesRepPermissions')
        );
});

it('denies sales_rep access to the permissions settings page', function () {
    $salesRep = User::factory()->create()->assignRole('sales_rep');

    $this->actingAs($salesRep)
        ->get(route('settings.permissions'))
        ->assertForbidden();
});

it('denies unauthenticated users access to permissions settings', function () {
    $this->get(route('settings.permissions'))
        ->assertRedirect(route('login'));
});

// ─── Permission Sync ──────────────────────────────────────────────────────────

it('admin can sync permissions for sales_rep role', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $salesRep = Role::findByName('sales_rep');

    $this->actingAs($admin)
        ->put(route('roles.permissions.update', ['role' => $salesRep->id]), [
            'permissions' => ['sales.create', 'sales.manage', 'customers.manage', 'reports.view_own'],
        ])
        ->assertOk()
        ->assertJsonFragment(['message' => 'Permissions updated successfully.']);

    expect($salesRep->fresh()->permissions->pluck('name'))
        ->toContain('sales.manage')
        ->toContain('customers.manage');
});

it('syncing replaces permissions (not additive)', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $salesRep = Role::findByName('sales_rep');

    // First sync: add many permissions
    $salesRep->syncPermissions(['sales.create', 'sales.manage', 'reports.view_own']);

    // Second sync: remove sales.manage
    $this->actingAs($admin)
        ->put(route('roles.permissions.update', ['role' => $salesRep->id]), [
            'permissions' => ['sales.create', 'reports.view_own'],
        ])
        ->assertOk();

    expect($salesRep->fresh()->permissions->pluck('name'))
        ->not->toContain('sales.manage');
});

it('prevents modifying the admin role permissions', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $adminRole = Role::findByName('admin');

    $this->actingAs($admin)
        ->put(route('roles.permissions.update', ['role' => $adminRole->id]), [
            'permissions' => ['sales.create'],
        ])
        ->assertForbidden();
});

it('rejects invalid permission names', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $salesRep = Role::findByName('sales_rep');

    $this->actingAs($admin)
        ->put(route('roles.permissions.update', ['role' => $salesRep->id]), [
            'permissions' => ['not.a.real.permission'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

it('requires permissions field to be an array', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $salesRep = Role::findByName('sales_rep');

    $this->actingAs($admin)
        ->put(route('roles.permissions.update', ['role' => $salesRep->id]), [
            'permissions' => 'sales.create',
        ])
        ->assertSessionHasErrors('permissions');
});

// ─── Permission Enforcement ───────────────────────────────────────────────────

it('sales_rep with sales.manage can access sales management route', function () {
    $salesRep = User::factory()->create()->assignRole('sales_rep');
    $salesRepRole = Role::findByName('sales_rep');
    $salesRepRole->givePermissionTo('sales.manage');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    expect($salesRep->fresh()->can('sales.manage'))->toBeTrue();
});

it('sales_rep without sales.manage cannot access sales management route', function () {
    $salesRep = User::factory()->create()->assignRole('sales_rep');

    expect($salesRep->can('sales.manage'))->toBeFalse();
});

it('user can method returns true for permissions inherited via role', function () {
    $admin = User::factory()->create()->assignRole('admin');

    expect($admin->can('users.manage'))->toBeTrue()
        ->and($admin->can('settings.manage'))->toBeTrue()
        ->and($admin->can('reports.view_all'))->toBeTrue();
});

// ─── Inertia Shared Props ─────────────────────────────────────────────────────

it('shares user permissions in Inertia props', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.permissions')
            ->where('auth.user.permissions', fn ($perms) => count($perms) === 16)
        );
});

it('sales_rep sees only their assigned permissions in shared props', function () {
    $salesRep = User::factory()->create()->assignRole('sales_rep');

    $this->actingAs($salesRep)
        ->get(route('pos.index'))
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.permissions')
            ->where('auth.user.permissions', fn ($perms) =>
                in_array('sales.create', $perms) &&
                !in_array('users.manage', $perms)
            )
        );
});

// ─── Activity Logging ─────────────────────────────────────────────────────────

it('logs permission sync in activity log', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $salesRep = Role::findByName('sales_rep');

    $this->actingAs($admin)
        ->put(route('roles.permissions.update', ['role' => $salesRep->id]), [
            'permissions' => ['sales.create'],
        ]);

    $this->assertDatabaseHas('activity_log', [
        'log_name'     => 'permissions',
        'description'  => 'permissions_synced',
        'subject_type' => Role::class,
        'subject_id'   => $salesRep->id,
        'causer_id'    => $admin->id,
    ]);
});
```

## Coverage Goals
- Seeder correctness (permission count, role count, default assignments)
- Access control (admin allowed, sales_rep denied, guest redirected)
- Permission sync (replace semantics, invalid names rejected, admin role immutable)
- `can()` checks inherited from role assignments
- Inertia shared props contain correct permissions per role
- Activity logging on permission changes
