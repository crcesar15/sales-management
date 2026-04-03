<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Store;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

/*
|--------------------------------------------------------------------------
| Assign Users via Update
|--------------------------------------------------------------------------
*/

it('assigns users to a store through update', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $user = User::factory()->create();

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => $store->name,
            'code' => $store->code,
            'status' => $store->status,
            'users' => [$user->id],
        ])
        ->assertRedirect(route('stores'));

    assertDatabaseHas('store_user', [
        'store_id' => $store->id,
        'user_id' => $user->id,
    ]);
});

it('syncs users replacing previous assignments', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $oldUser = User::factory()->create();
    $newUser = User::factory()->create();

    $store->users()->attach($oldUser);

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => $store->name,
            'code' => $store->code,
            'status' => $store->status,
            'users' => [$newUser->id],
        ])
        ->assertRedirect(route('stores'));

    assertDatabaseHas('store_user', [
        'store_id' => $store->id,
        'user_id' => $newUser->id,
    ]);

    $this->assertDatabaseMissing('store_user', [
        'store_id' => $store->id,
        'user_id' => $oldUser->id,
    ]);
});

it('removes all users when empty array passed', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $user = User::factory()->create();
    $store->users()->attach($user);

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => $store->name,
            'code' => $store->code,
            'status' => $store->status,
            'users' => [],
        ])
        ->assertRedirect(route('stores'));

    $this->assertDatabaseMissing('store_user', [
        'store_id' => $store->id,
        'user_id' => $user->id,
    ]);
});

it('validates users must exist in database', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => $store->name,
            'code' => $store->code,
            'status' => $store->status,
            'users' => [999999],
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['users.0']);
});

/*
|--------------------------------------------------------------------------
| Activity Logging for User Changes
|--------------------------------------------------------------------------
*/

it('logs activity when users are assigned', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $store = Store::factory()->create();
    $user = User::factory()->create();

    actingAs($admin)
        ->put(route('stores.update', $store), [
            'name' => $store->name,
            'code' => $store->code,
            'status' => $store->status,
            'users' => [$user->id],
        ]);

    $activity = Activity::query()
        ->where('subject_type', Store::class)
        ->where('subject_id', $store->id)
        ->where('description', 'updated')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('store');
    expect($activity->causer_id)->toBe($admin->id);
});
