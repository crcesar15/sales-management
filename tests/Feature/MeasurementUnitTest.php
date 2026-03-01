<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\MeasurementUnit;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;

it('admin user can list measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newMeasurementUnit = MeasurementUnit::factory()->create();

    actingAs($user)
        ->get(route('measurement-units'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.v1.measurement-units'))
        ->assertStatus(200);

    actingAs($user)
        ->get(route('api.v1.measurement-units.show', $newMeasurementUnit->id))
        ->assertStatus(200);
});

it('admin user can creates measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $word = fake()->word;

    $newMeasurementUnit = [
        'name' => $word,
        'abbreviation' => mb_strtoupper(mb_substr($word, 0, 2)),
    ];

    actingAs($user)
        ->post(route('api.v1.measurement-units.store'), $newMeasurementUnit)
        ->assertStatus(201);

    $latestMeasurementUnit = MeasurementUnit::latest('id')->firstOrFail();

    expect($latestMeasurementUnit->name)->toBe($newMeasurementUnit['name']);
    expect($latestMeasurementUnit->abbreviation)->toBe($newMeasurementUnit['abbreviation']);
});

it('creating measurement units with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $word = fake()->word;

    $newMeasurementUnit = [
        'name' => $word,
        'abbreviation' => 'Too_long_abbreviation',
    ];

    actingAs($user)
        ->post(route('api.v1.measurement-units.store'), $newMeasurementUnit, ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('admin user can update measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newMeasurementUnit = MeasurementUnit::factory()->create();

    $word = fake()->word;

    $updateMeasurementUnitData = [
        'name' => $word,
        'abbreviation' => mb_strtoupper(mb_substr($word, 0, 2)),
    ];

    actingAs($user)
        ->put(route('api.v1.measurement-units.update', $newMeasurementUnit->id), $updateMeasurementUnitData)
        ->assertStatus(200);

    $updatedMeasurementUnit = MeasurementUnit::findOrFail($newMeasurementUnit->id);

    expect($updatedMeasurementUnit->name)->toBe($updateMeasurementUnitData['name']);
    expect($updatedMeasurementUnit->abbreviation)->toBe($updateMeasurementUnitData['abbreviation']);
});

it('updating measurement units with invalid data', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newMeasurementUnit = MeasurementUnit::factory()->create();

    $word = fake()->word;

    $updateMeasurementUnitData = [
        'name' => $word,
        'abbreviation' => 'Too_long_abbreviation',
    ];

    actingAs($user)
        ->put(
            route('api.v1.measurement-units.update', $newMeasurementUnit->id),
            $updateMeasurementUnitData,
            ['Accept' => 'application/json']
        )
        ->assertStatus(422);
});

it('admin user can delete measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $newMeasurementUnit = MeasurementUnit::factory()->create();

    actingAs($user)
        ->delete(route('api.v1.measurement-units.destroy', $newMeasurementUnit))
        ->assertStatus(204);

    assertSoftDeleted($newMeasurementUnit);
});

it('admin user can restore measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $measurementUnit = MeasurementUnit::factory()->create();
    $measurementUnit->delete();

    assertSoftDeleted($measurementUnit);

    actingAs($user)
        ->put(route('api.v1.measurement-units.restore', $measurementUnit->id))
        ->assertStatus(204);

    expect(MeasurementUnit::find($measurementUnit->id))->not->toBeNull();
});

it('admin user can list archived measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $measurementUnit = MeasurementUnit::factory()->create();
    $measurementUnit->delete();

    actingAs($user)
        ->get(route('api.v1.measurement-units', ['status' => 'archived']))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

it('non-admin user cannot creates measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newMeasurementUnit = MeasurementUnit::factory()->create();

    actingAs($user)
        ->get(route('measurement-units'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.v1.measurement-units'))
        ->assertStatus(403);

    actingAs($user)
        ->get(route('api.v1.measurement-units.show', $newMeasurementUnit->id))
        ->assertStatus(403);
});

it('non-admin user cannot create measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $word = fake()->word;

    $newMeasurementUnit = [
        'name' => $word,
        'abbreviation' => mb_strtoupper(mb_substr($word, 0, 2)),
    ];

    actingAs($user)
        ->post(route('api.v1.measurement-units.store'), $newMeasurementUnit)
        ->assertStatus(403);
});

it('non-admin user cannot update measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newMeasurementUnit = MeasurementUnit::factory()->create();

    $word = fake()->word;

    $updateMeasurementUnitData = [
        'name' => $word,
        'abbreviation' => mb_strtoupper(mb_substr($word, 0, 2)),
    ];

    actingAs($user)
        ->put(route('api.v1.measurement-units.update', $newMeasurementUnit->id), $updateMeasurementUnitData)
        ->assertStatus(403);
});

it('non-admin user cannot delete measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $newMeasurementUnit = MeasurementUnit::factory()->create();

    actingAs($user)
        ->delete(route('api.v1.measurement-units.destroy', $newMeasurementUnit))
        ->assertStatus(403);
});

it('non-admin user cannot restore measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $measurementUnit = MeasurementUnit::factory()->create();
    $measurementUnit->delete();

    actingAs($user)
        ->put(route('api.v1.measurement-units.restore', $measurementUnit->id))
        ->assertStatus(403);
});
