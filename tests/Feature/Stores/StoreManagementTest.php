<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Store;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

it('admin can list stores', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Store::factory()->create(['name' => 'Test Store']);

    actingAs($admin)
        ->get(route('stores'))
        ->assertStatus(200);
});

it('admin can view create store page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('stores.create'))
        ->assertStatus(200);
});

it('admin can view edit store page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();

    actingAs($admin)
        ->get(route('stores.edit', $store))
        ->assertStatus(200);
});

it('salesman is denied store listing', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('stores'))
        ->assertForbidden();
});

it('salesman is denied create store page', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('stores.create'))
        ->assertForbidden();
});

it('salesman is denied creating a store', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->postJson(route('stores.store'), [
            'name' => 'Blocked',
            'code' => 'BLK',
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('salesman is denied updating a store', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    $store = Store::factory()->create();

    actingAs($salesman)
        ->putJson(route('stores.update', $store), [
            'name' => 'Blocked',
            'code' => $store->code,
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('salesman is denied deleting a store', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    $store = Store::factory()->create();

    actingAs($salesman)
        ->deleteJson(route('stores.destroy', $store))
        ->assertForbidden();
});

it('salesman is denied restoring a store', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    $store = Store::factory()->create();
    $store->delete();

    actingAs($salesman)
        ->putJson(route('stores.restore', $store))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Create Store
|--------------------------------------------------------------------------
*/

it('creates a store with valid data', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $storeData = [
        'name' => 'Acme Store',
        'code' => 'acm01',
        'status' => 'active',
        'address' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'zip_code' => '62701',
        'phone' => '555-1234',
        'email' => 'store@acme.com',
    ];

    actingAs($admin)
        ->post(route('stores.store'), $storeData)
        ->assertRedirect(route('stores'));

    assertDatabaseHas('stores', [
        'name' => 'Acme Store',
        'city' => 'Springfield',
        'email' => 'store@acme.com',
    ]);
});

it('uppercases store code on creation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('stores.store'), [
            'name' => 'Lowercase Code',
            'code' => 'low01',
            'status' => 'active',
        ])
        ->assertRedirect(route('stores'));

    assertDatabaseHas('stores', ['code' => 'LOW01']);
});

it('validates required fields on create', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('stores.store'), [], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'code', 'status']);
});

it('validates email format on create', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('stores.store'), [
            'name' => 'Store',
            'code' => 'EMA01',
            'status' => 'active',
            'email' => 'not-an-email',
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates field max lengths on create', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('stores.store'), [
            'name' => 'Store',
            'code' => 'LN01',
            'status' => 'active',
            'zip_code' => str_repeat('x', 21),
            'phone' => str_repeat('x', 31),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['zip_code', 'phone']);
});

it('enforces unique store code on create', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Store::factory()->create(['code' => 'UNQ01']);

    actingAs($admin)
        ->post(route('stores.store'), [
            'name' => 'Duplicate Code',
            'code' => 'UNQ01',
            'status' => 'active',
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

/*
|--------------------------------------------------------------------------
| Update Store
|--------------------------------------------------------------------------
*/

it('updates a store successfully', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => 'Updated Store',
            'code' => $store->code,
            'status' => 'inactive',
            'address' => '456 Oak Ave',
            'city' => 'Portland',
            'state' => 'OR',
            'zip_code' => '97201',
            'phone' => '555-9999',
            'email' => 'updated@store.com',
        ])
        ->assertRedirect(route('stores'));

    assertDatabaseHas('stores', [
        'id' => $store->id,
        'name' => 'Updated Store',
        'address' => '456 Oak Ave',
        'phone' => '555-9999',
        'email' => 'updated@store.com',
    ]);
});

it('allows update with same code without unique violation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['code' => 'OWN01']);

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => 'Same Code',
            'code' => 'OWN01',
            'status' => 'active',
        ])
        ->assertRedirect(route('stores'));

    assertDatabaseHas('stores', [
        'id' => $store->id,
        'name' => 'Same Code',
        'code' => 'OWN01',
    ]);
});

it('validates required fields on update', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();

    actingAs($admin)
        ->put(route('stores.update', $store), [], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'code', 'status']);
});

/*
|--------------------------------------------------------------------------
| Status Toggle
|--------------------------------------------------------------------------
*/

it('changes store status to inactive', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['status' => 'active']);

    actingAs($admin)
        ->patch(route('stores.status', $store), ['status' => 'inactive'])
        ->assertRedirect();

    assertDatabaseHas('stores', [
        'id' => $store->id,
        'status' => 'inactive',
    ]);
});

/*
|--------------------------------------------------------------------------
| Soft Delete
|--------------------------------------------------------------------------
*/

it('soft-deletes a store', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();

    actingAs($admin)
        ->delete(route('stores.destroy', $store))
        ->assertRedirect(route('stores'));

    assertSoftDeleted($store);
});

it('soft-deleted store does not appear in active listing', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['name' => 'Visible Store']);
    $deletedStore = Store::factory()->create(['name' => 'Deleted Store']);
    $deletedStore->delete();

    $response = actingAs($admin)
        ->get(route('stores'))
        ->assertStatus(200);

    $activeStores = Store::withoutGlobalScopes()->count();
    expect($activeStores)->toBeGreaterThanOrEqual(2);

    expect(Store::query()->count())->toBe(1);
    expect(Store::query()->first()->name)->toBe('Visible Store');
});

it('store code uniqueness includes soft-deleted stores', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['code' => 'DEL01']);
    $store->delete();

    actingAs($admin)
        ->post(route('stores.store'), [
            'name' => 'Reuse Code',
            'code' => 'DEL01',
            'status' => 'active',
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

/*
|--------------------------------------------------------------------------
| Restore
|--------------------------------------------------------------------------
*/

it('restores a soft-deleted store', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $store->delete();

    assertSoftDeleted($store);

    actingAs($admin)
        ->put(route('stores.restore', $store))
        ->assertRedirect(route('stores'));

    assertDatabaseHas('stores', [
        'id' => $store->id,
        'deleted_at' => null,
    ]);
});

it('restored store appears in active listing', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['name' => 'Restored Store', 'status' => 'active']);
    $store->delete();

    actingAs($admin)
        ->put(route('stores.restore', $store))
        ->assertRedirect(route('stores'));

    expect(Store::query()->where('id', $store->id)->exists())->toBeTrue();
});

it('restored store status resets to active', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['status' => 'inactive']);
    $store->delete();

    actingAs($admin)
        ->put(route('stores.restore', $store));

    assertDatabaseHas('stores', [
        'id' => $store->id,
        'status' => 'active',
    ]);
});

/*
|--------------------------------------------------------------------------
| Activity Logging
|--------------------------------------------------------------------------
*/

it('logs activity when creating a store', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('stores.store'), [
            'name' => 'Logged Store',
            'code' => 'LOG01',
            'status' => 'active',
        ]);

    $store = Store::where('code', 'LOG01')->firstOrFail();

    $activity = Activity::query()
        ->where('subject_type', Store::class)
        ->where('subject_id', $store->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('store');
    expect($activity->causer_id)->toBe($admin->id);
});

it('logs activity when updating a store', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create(['name' => 'Original']);

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => 'Changed',
            'code' => $store->code,
            'status' => $store->status,
        ]);

    $activity = Activity::query()
        ->where('subject_type', Store::class)
        ->where('subject_id', $store->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('store');
});

it('logs activity when deleting a store', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();

    actingAs($admin)
        ->delete(route('stores.destroy', $store));

    $activity = Activity::query()
        ->where('subject_type', Store::class)
        ->where('subject_id', $store->id)
        ->where('event', 'deleted')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('store');
    expect($activity->causer_id)->toBe($admin->id);
});

it('logs activity when restoring a store', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $store->delete();

    actingAs($admin)
        ->put(route('stores.restore', $store));

    $activity = Activity::query()
        ->where('subject_type', Store::class)
        ->where('subject_id', $store->id)
        ->where('event', 'restored')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('store');
});
