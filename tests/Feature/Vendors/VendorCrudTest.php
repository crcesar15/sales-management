<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use App\Models\Vendor;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

// ─── Authorization ────────────────────────────────────────────────────────────

it('guest is redirected to login', function () {
    get(route('vendors'))
        ->assertRedirect(route('login'));
});

it('user without permission receives 403', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->getJson(route('vendors'))
        ->assertForbidden();
});

it('admin with permission can access the page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('vendors'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Index')
            ->has('vendors')
            ->has('filters')
        );
});

// ─── List ─────────────────────────────────────────────────────────────────────

it('returns paginated vendors ordered by fullname', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Vendor::factory()->create(['fullname' => 'Zebra Corp']);
    Vendor::factory()->create(['fullname' => 'Alpha Inc']);
    Vendor::factory()->create(['fullname' => 'Middle LLC']);

    actingAs($admin)
        ->get(route('vendors'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Index')
            ->where('vendors.data.0.fullname', 'Alpha Inc')
            ->where('vendors.data.1.fullname', 'Middle LLC')
            ->where('vendors.data.2.fullname', 'Zebra Corp')
            ->has('vendors.meta')
        );
});

it('search by fullname filters correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Vendor::factory()->create(['fullname' => 'Acme Corporation']);
    Vendor::factory()->create(['fullname' => 'Globex']);

    actingAs($admin)
        ->get(route('vendors', ['filter' => 'Acme']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Index')
            ->where('vendors.data.0.fullname', 'Acme Corporation')
            ->where('vendors.meta.total', 1)
        );
});

it('search by email filters correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Vendor::factory()->create(['email' => 'acme@example.com']);
    Vendor::factory()->create(['email' => 'globex@example.com']);

    actingAs($admin)
        ->get(route('vendors', ['filter' => 'acme']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Index')
            ->where('vendors.data.0.email', 'acme@example.com')
            ->where('vendors.meta.total', 1)
        );
});

it('filter by status returns only matching vendors', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Vendor::factory()->create(['fullname' => 'Active Vendor', 'status' => 'active']);
    Vendor::factory()->create(['fullname' => 'Inactive Vendor', 'status' => 'inactive']);

    actingAs($admin)
        ->get(route('vendors', ['status' => 'inactive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Index')
            ->where('vendors.meta.total', 1)
            ->where('vendors.data.0.fullname', 'Inactive Vendor')
        );
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('admin creates a vendor', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('vendors.store'), [
            'fullname' => 'Acme Corp',
            'status' => 'active',
        ])
        ->assertRedirect(route('vendors'));

    expect(Vendor::where('fullname', 'Acme Corp')->exists())->toBeTrue();
});

it('additional_contacts JSON is persisted correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $contacts = [
        ['name' => 'John Doe', 'phone' => '555-1234', 'email' => 'john@acme.com', 'role' => 'Billing'],
    ];

    actingAs($admin)
        ->post(route('vendors.store'), [
            'fullname' => 'Acme Corp',
            'status' => 'active',
            'additional_contacts' => $contacts,
        ])
        ->assertRedirect(route('vendors'));

    $vendor = Vendor::where('fullname', 'Acme Corp')->first();
    expect($vendor->additional_contacts)->toBe($contacts);
});

it('missing fullname returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('vendors.store'), [
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['fullname']);
});

it('duplicate email returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Vendor::factory()->create(['email' => 'taken@example.com']);

    actingAs($admin)
        ->post(route('vendors.store'), [
            'fullname' => 'New Vendor',
            'email' => 'taken@example.com',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['email']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin updates a vendor', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create(['fullname' => 'Old Name']);

    actingAs($admin)
        ->put(route('vendors.update', $vendor), [
            'fullname' => 'New Name',
            'status' => 'active',
        ])
        ->assertRedirect(route('vendors'));

    $vendor->refresh();
    expect($vendor->fullname)->toBe('New Name');
});

it('can update vendor keeping same email', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $vendor = Vendor::factory()->create(['email' => 'keep@example.com']);

    actingAs($admin)
        ->put(route('vendors.update', $vendor), [
            'fullname' => $vendor->fullname,
            'email' => 'keep@example.com',
            'status' => 'active',
        ])
        ->assertRedirect(route('vendors'));
});

it('cannot update vendor with another vendor email', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Vendor::factory()->create(['email' => 'taken@example.com']);
    $vendor = Vendor::factory()->create(['email' => 'original@example.com']);

    actingAs($admin)
        ->put(route('vendors.update', $vendor), [
            'fullname' => $vendor->fullname,
            'email' => 'taken@example.com',
            'status' => 'active',
        ])
        ->assertSessionHasErrors(['email']);
});
