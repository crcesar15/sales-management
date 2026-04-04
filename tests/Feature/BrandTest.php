<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\Brand;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\get;

// ─── Authorization ────────────────────────────────────────────────────────────

it('guest is redirected to login', function () {
    get(route('brands'))
        ->assertRedirect(route('login'));
});

it('user without permission receives 403', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->getJson(route('brands'))
        ->assertForbidden();
});

it('admin with permission can access the page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('brands'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Brands/Index')
            ->has('brands')
            ->has('filters')
        );
});

// ─── List ─────────────────────────────────────────────────────────────────────

it('returns paginated brands ordered by name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Brand::factory()->create(['name' => 'Zebra Corp']);
    Brand::factory()->create(['name' => 'Alpha Inc']);
    Brand::factory()->create(['name' => 'Middle LLC']);

    actingAs($admin)
        ->get(route('brands'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Brands/Index')
            ->where('brands.data.0.name', 'Alpha Inc')
            ->where('brands.data.1.name', 'Middle LLC')
            ->where('brands.data.2.name', 'Zebra Corp')
            ->has('brands.meta')
        );
});

it('search by name filters correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Brand::factory()->create(['name' => 'Acme Corporation']);
    Brand::factory()->create(['name' => 'Globex']);

    actingAs($admin)
        ->get(route('brands', ['filter' => 'Acme']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Brands/Index')
            ->where('brands.data.0.name', 'Acme Corporation')
            ->where('brands.meta.total', 1)
        );
});

it('soft-deleted records excluded from default list', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create(['name' => 'Deleted Brand']);
    $brand->delete();
    Brand::factory()->create(['name' => 'Active Brand']);

    actingAs($admin)
        ->get(route('brands'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Brands/Index')
            ->where('brands.meta.total', 1)
            ->where('brands.data.0.name', 'Active Brand')
        );
});

it('status=archived returns only soft-deleted records', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create(['name' => 'Deleted Brand']);
    $brand->delete();
    Brand::factory()->create(['name' => 'Active Brand']);

    actingAs($admin)
        ->get(route('brands', ['status' => 'archived']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Brands/Index')
            ->where('brands.meta.total', 1)
            ->where('brands.data.0.name', 'Deleted Brand')
        );
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('admin creates a brand', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('brands.store'), ['name' => 'Acme Corp'])
        ->assertRedirect(route('brands'));

    expect(Brand::where('name', 'Acme Corp')->exists())->toBeTrue();
});

it('empty name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('brands.store'), ['name' => ''])
        ->assertSessionHasErrors(['name']);
});

it('name exceeding 50 chars returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('brands.store'), ['name' => str_repeat('a', 51)])
        ->assertSessionHasErrors(['name']);
});

it('duplicate name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Brand::factory()->create(['name' => 'Acme Corp']);

    actingAs($admin)
        ->post(route('brands.store'), ['name' => 'Acme Corp'])
        ->assertSessionHasErrors(['name']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin updates a brand name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create(['name' => 'Old Name']);

    actingAs($admin)
        ->put(route('brands.update', $brand), ['name' => 'New Name'])
        ->assertRedirect(route('brands'));

    $brand->refresh();
    expect($brand->name)->toBe('New Name');
});

it('updating with the same name passes unique rule', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create(['name' => 'Acme Corp']);

    actingAs($admin)
        ->put(route('brands.update', $brand), ['name' => 'Acme Corp'])
        ->assertRedirect(route('brands'));

    $brand->refresh();
    expect($brand->name)->toBe('Acme Corp');
});

it('renaming to an existing name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    Brand::factory()->create(['name' => 'Taken']);
    $brand = Brand::factory()->create(['name' => 'Other']);

    actingAs($admin)
        ->put(route('brands.update', $brand), ['name' => 'Taken'])
        ->assertSessionHasErrors(['name']);
});

// ─── Soft Delete ──────────────────────────────────────────────────────────────

it('admin deletes a brand', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create();

    actingAs($admin)
        ->delete(route('brands.destroy', $brand))
        ->assertRedirect(route('brands'));

    assertSoftDeleted($brand);
});

it('brand with active products cannot be deleted', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create();
    $unit = MeasurementUnit::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id, 'measurement_unit_id' => $unit->id]);

    actingAs($admin)
        ->delete(route('brands.destroy', $brand))
        ->assertRedirect()
        ->assertSessionHas('error');

    $brand->refresh();
    expect($brand->deleted_at)->toBeNull();
});

// ─── Restore ──────────────────────────────────────────────────────────────────

it('admin restores a soft-deleted brand', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $brand = Brand::factory()->create();
    $brand->delete();

    assertSoftDeleted($brand);

    actingAs($admin)
        ->put(route('brands.restore', $brand->id))
        ->assertRedirect(route('brands'));

    $brand->refresh();
    expect($brand->deleted_at)->toBeNull();
});

// ─── Permission Denials ──────────────────────────────────────────────────────

it('non-admin cannot create brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->post(route('brands.store'), ['name' => 'Test'])
        ->assertForbidden();
});

it('non-admin cannot update brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $brand = Brand::factory()->create();

    actingAs($user)
        ->put(route('brands.update', $brand), ['name' => 'Test'])
        ->assertForbidden();
});

it('non-admin cannot delete brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $brand = Brand::factory()->create();

    actingAs($user)
        ->delete(route('brands.destroy', $brand))
        ->assertForbidden();
});

it('non-admin cannot restore brands', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $brand = Brand::factory()->create();
    $brand->delete();

    actingAs($user)
        ->put(route('brands.restore', $brand->id))
        ->assertForbidden();
});
