<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

it('creates activity log when user is created', function () {
    $user = User::factory()->create();

    $activity = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity?->log_name)->toBe('user');
    expect($activity?->properties['attributes'] ?? [])->toHaveKeys(['first_name', 'last_name', 'email', 'username']);
});

it('creates activity log when user is updated', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $user = User::factory()->create();

    $originalName = $user->first_name;
    $user->update(['first_name' => 'UpdatedName']);

    $activity = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();

    $activityProperties = $activity?->properties;

    if (
        isset($activityProperties['attributes'])
            && is_array($activityProperties['attributes'])
            && isset($activityProperties['attributes']['first_name'])
    ) {
        $currentFirstName = $activityProperties['attributes']['first_name'];
    } else {
        $currentFirstName = null;
    }

    if (
        isset($activityProperties['old'])
            && is_array($activityProperties['old'])
            && isset($activityProperties['old']['first_name'])
    ) {
        $oldFirstName = $activityProperties['old']['first_name'];
    } else {
        $oldFirstName = null;
    }

    expect($currentFirstName)->toBe('UpdatedName');
    expect($oldFirstName)->toBe($originalName);
});

it('does not log password in activity properties', function () {
    $user = User::factory()->create();

    $user->update(['password' => 'NewPassword123#']);

    $activities = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->get();

    $activities->each(function (Activity $activity) {
        $attributes = $activity?->properties['attributes'] ?? [];
        $old = $activity?->properties['old'] ?? [];

        expect($attributes)->not->toHaveKey('password');
        expect($old)->not->toHaveKey('password');
    });
});

it('creates activity log when user is soft deleted', function () {
    $user = User::factory()->create();

    $user->delete();

    $activity = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'deleted')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity?->log_name)->toBe('user');
});

it('creates activity log when user is restored', function () {
    $user = User::factory()->create();
    $user->delete();

    $user->restore();

    $activity = Activity::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'restored')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity?->log_name)->toBe('user');
});

it('authorized user can list all activity logs via API', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    User::factory()->create();

    actingAs($admin)
        ->getJson(route('api.v1.activity-logs'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'log_name',
                    'description',
                    'event',
                    'subject_type',
                    'subject_id',
                    'properties' => ['old', 'attributes'],
                    'created_at',
                ],
            ],
            'meta' => ['total'],
        ]);
});

it('unauthorized user cannot list activity logs via API', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('api.v1.activity-logs'))
        ->assertForbidden();
});

it('can filter activity logs by event type', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $user = User::factory()->create();
    $user->update(['first_name' => 'Changed']);

    actingAs($admin)
        ->getJson(route('api.v1.activity-logs', ['event' => 'updated']))
        ->assertSuccessful()
        ->assertJsonPath('data.0.event', 'updated');
});

it('can search activity logs with filter parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    User::factory()->create();

    actingAs($admin)
        ->getJson(route('api.v1.activity-logs', ['filter' => 'user']))
        ->assertSuccessful();
});

it('renders the activity logs page for authorized user', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('activity-logs'))
        ->assertSuccessful();
});

it('unauthorized user cannot access activity logs page', function () {
    $salesman = User::factory()->create();
    $salesman->assignRole(RolesEnum::SALESMAN);

    actingAs($salesman)
        ->getJson(route('activity-logs'))
        ->assertForbidden();
});
