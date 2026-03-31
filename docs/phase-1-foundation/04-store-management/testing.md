# Store Management — Testing

## Test File Location
```
tests/Feature/Stores/StoreManagementTest.php
tests/Feature/Stores/StoreLogoTest.php
tests/Feature/Stores/StoreUserAssignmentTest.php
```

## Test Cases

```php
<?php

// tests/Feature/Stores/StoreManagementTest.php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

// ─── Access Control ───────────────────────────────────────────────────────────

it('allows admin to list stores', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Store::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('stores.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Stores/Index')
            ->has('stores.data', 3)
        );
});

it('denies sales_rep access to stores index', function () {
    $salesRep = User::factory()->create()->assignRole('sales_rep');

    $this->actingAs($salesRep)
        ->get(route('stores.index'))
        ->assertForbidden();
});

// ─── Create Store ─────────────────────────────────────────────────────────────

it('creates a store with valid data', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('stores.store'), [
            'name'    => 'Main Store',
            'code'    => 'HQ',
            'address' => '123 Main St',
            'status'  => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('stores', [
        'name' => 'Main Store',
        'code' => 'HQ',
    ]);
});

it('uppercases the store code on creation', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('stores.store'), [
            'name'   => 'Branch',
            'code'   => 'br1',
            'status' => 'active',
        ]);

    $this->assertDatabaseHas('stores', ['code' => 'BR1']);
});

it('validates required fields on store creation', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('stores.store'), [])
        ->assertSessionHasErrors(['name', 'code', 'status']);
});

it('enforces unique store code', function () {
    $admin = User::factory()->create()->assignRole('admin');
    Store::factory()->create(['code' => 'HQ']);

    $this->actingAs($admin)
        ->post(route('stores.store'), [
            'name'   => 'Another Store',
            'code'   => 'HQ',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('code');
});

it('validates logo is an image file', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('stores.store'), [
            'name'   => 'Test Store',
            'code'   => 'TS1',
            'status' => 'active',
            'logo'   => $file,
        ])
        ->assertSessionHasErrors('logo');
});

it('validates logo does not exceed 2MB', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $file = UploadedFile::fake()->image('logo.jpg')->size(3000); // 3MB

    $this->actingAs($admin)
        ->post(route('stores.store'), [
            'name'   => 'Test Store',
            'code'   => 'TS2',
            'status' => 'active',
            'logo'   => $file,
        ])
        ->assertSessionHasErrors('logo');
});

// ─── Update Store ─────────────────────────────────────────────────────────────

it('updates a store', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->put(route('stores.update', $store), [
            'name'    => 'New Name',
            'code'    => $store->code,
            'address' => $store->address,
            'status'  => 'active',
        ])
        ->assertRedirect();

    expect($store->fresh()->name)->toBe('New Name');
});

it('allows updating a store with its own code', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create(['code' => 'HQ']);

    $this->actingAs($admin)
        ->put(route('stores.update', $store), [
            'name'   => 'Updated Name',
            'code'   => 'HQ', // same code — should not trigger unique violation
            'status' => 'active',
        ])
        ->assertRedirect();
});

// ─── Status Toggle ────────────────────────────────────────────────────────────

it('changes store status to inactive', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create(['status' => 'active']);

    $this->actingAs($admin)
        ->patch(route('stores.status', $store), ['status' => 'inactive'])
        ->assertRedirect();

    expect($store->fresh()->status)->toBe('inactive');
});
```

```php
<?php

// tests/Feature/Stores/StoreLogoTest.php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

it('uploads a store logo on create', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $file = UploadedFile::fake()->image('logo.jpg', 200, 200);

    $this->actingAs($admin)
        ->post(route('stores.store'), [
            'name'   => 'Logo Store',
            'code'   => 'LS1',
            'status' => 'active',
            'logo'   => $file,
        ]);

    $store = Store::where('code', 'LS1')->firstOrFail();
    expect($store->getFirstMedia('logo'))->not->toBeNull();
    expect($store->logo_url)->not->toBeNull();
});

it('replaces the logo when a new one is uploaded on update', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create();
    $firstFile = UploadedFile::fake()->image('first.jpg');
    $store->addMedia($firstFile)->toMediaCollection('logo');

    $originalMediaId = $store->getFirstMedia('logo')->id;

    $newFile = UploadedFile::fake()->image('new.jpg');
    $this->actingAs($admin)
        ->post(route('stores.update', $store), [
            '_method' => 'PUT',
            'name'    => $store->name,
            'code'    => $store->code,
            'status'  => 'active',
            'logo'    => $newFile,
        ]);

    expect($store->fresh()->getFirstMedia('logo')->id)->not->toBe($originalMediaId);
    expect($store->fresh()->getMedia('logo'))->toHaveCount(1);
});

it('removes the store logo', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create();
    $file = UploadedFile::fake()->image('logo.jpg');
    $store->addMedia($file)->toMediaCollection('logo');

    $this->actingAs($admin)
        ->delete(route('stores.logo.remove', $store))
        ->assertRedirect();

    expect($store->fresh()->getFirstMedia('logo'))->toBeNull();
});
```

```php
<?php

// tests/Feature/Stores/StoreUserAssignmentTest.php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('assigns a user to a store', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create();
    $user  = User::factory()->create();
    $role  = Role::findByName('sales_rep');

    $this->actingAs($admin)
        ->post(route('stores.users.assign', $store), [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('store_user', [
        'store_id' => $store->id,
        'user_id'  => $user->id,
        'role_id'  => $role->id,
    ]);
});

it('prevents assigning the same user to a store twice', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create();
    $user  = User::factory()->create();
    $role  = Role::findByName('sales_rep');

    $store->users()->attach($user->id, ['role_id' => $role->id]);

    $this->actingAs($admin)
        ->post(route('stores.users.assign', $store), [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ])
        ->assertSessionHasErrors();
});

it('removes a user from a store', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create();
    $user  = User::factory()->create();
    $role  = Role::findByName('sales_rep');
    $store->users()->attach($user->id, ['role_id' => $role->id]);

    $this->actingAs($admin)
        ->delete(route('stores.users.remove', [$store, $user]))
        ->assertRedirect();

    $this->assertDatabaseMissing('store_user', [
        'store_id' => $store->id,
        'user_id'  => $user->id,
    ]);
});

it('updates a user role within a store', function () {
    $admin    = User::factory()->create()->assignRole('admin');
    $store    = Store::factory()->create();
    $user     = User::factory()->create();
    $salesRep = Role::findByName('sales_rep');
    $adminRole = Role::findByName('admin');

    $store->users()->attach($user->id, ['role_id' => $salesRep->id]);

    $this->actingAs($admin)
        ->patch(route('stores.users.role', [$store, $user]), [
            'role_id' => $adminRole->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('store_user', [
        'store_id' => $store->id,
        'user_id'  => $user->id,
        'role_id'  => $adminRole->id,
    ]);
});

it('logs user assignment in activity log', function () {
    $admin = User::factory()->create()->assignRole('admin');
    $store = Store::factory()->create();
    $user  = User::factory()->create();
    $role  = Role::findByName('sales_rep');

    $this->actingAs($admin)
        ->post(route('stores.users.assign', $store), [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('activity_log', [
        'log_name'    => 'stores',
        'description' => 'user_assigned',
        'subject_id'  => $store->id,
        'causer_id'   => $admin->id,
    ]);
});
```

## Coverage Goals
- All CRUD operations tested
- Permission enforcement (admin allowed, sales_rep denied)
- Validation rules (required fields, unique code, logo type and size)
- Store code is uppercased
- Update with same code does not trigger unique constraint violation
- Logo upload, replacement, and removal
- User assignment, duplicate prevention, role update, removal
- Activity logging for key actions
