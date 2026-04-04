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
    get(route('measurement-units'))
        ->assertRedirect(route('login'));
});

it('user without permission receives 403', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->getJson(route('measurement-units'))
        ->assertForbidden();
});

it('admin with permission can access the page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->get(route('measurement-units'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MeasurementUnits/Index')
            ->has('measurementUnits')
            ->has('filters')
        );
});

// ─── List ─────────────────────────────────────────────────────────────────────

it('returns paginated measurement units ordered by name', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    MeasurementUnit::factory()->create(['name' => 'Zinc']);
    MeasurementUnit::factory()->create(['name' => 'Alpha']);
    MeasurementUnit::factory()->create(['name' => 'Middle']);

    actingAs($admin)
        ->get(route('measurement-units'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MeasurementUnits/Index')
            ->where('measurementUnits.data.0.name', 'Alpha')
            ->where('measurementUnits.data.1.name', 'Middle')
            ->where('measurementUnits.data.2.name', 'Zinc')
            ->has('measurementUnits.meta')
        );
});

it('search by name filters correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    MeasurementUnit::factory()->create(['name' => 'Kilograms']);
    MeasurementUnit::factory()->create(['name' => 'Liters']);

    actingAs($admin)
        ->get(route('measurement-units', ['filter' => 'Kilo']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MeasurementUnits/Index')
            ->where('measurementUnits.data.0.name', 'Kilograms')
            ->where('measurementUnits.meta.total', 1)
        );
});

it('search by abbreviation filters correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    MeasurementUnit::factory()->create(['name' => 'Kilograms', 'abbreviation' => 'KG']);
    MeasurementUnit::factory()->create(['name' => 'Liters', 'abbreviation' => 'LT']);

    actingAs($admin)
        ->get(route('measurement-units', ['filter' => 'KG']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MeasurementUnits/Index')
            ->where('measurementUnits.meta.total', 1)
            ->where('measurementUnits.data.0.abbreviation', 'KG')
        );
});

it('soft-deleted records excluded from default list', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $unit = MeasurementUnit::factory()->create(['name' => 'Deleted']);
    $unit->delete();
    MeasurementUnit::factory()->create(['name' => 'Active']);

    actingAs($admin)
        ->get(route('measurement-units'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MeasurementUnits/Index')
            ->where('measurementUnits.meta.total', 1)
            ->where('measurementUnits.data.0.name', 'Active')
        );
});

it('status=archived returns only soft-deleted records', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $unit = MeasurementUnit::factory()->create(['name' => 'Deleted']);
    $unit->delete();
    MeasurementUnit::factory()->create(['name' => 'Active']);

    actingAs($admin)
        ->get(route('measurement-units', ['status' => 'archived']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MeasurementUnits/Index')
            ->where('measurementUnits.meta.total', 1)
            ->where('measurementUnits.data.0.name', 'Deleted')
        );
});

// ─── Create ───────────────────────────────────────────────────────────────────

it('admin creates a measurement unit', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('measurement-units.store'), [
            'name' => 'Kilograms',
            'abbreviation' => 'KG',
        ])
        ->assertRedirect(route('measurement-units'));

    expect(MeasurementUnit::where('name', 'Kilograms')->exists())->toBeTrue();
    expect(MeasurementUnit::where('abbreviation', 'KG')->exists())->toBeTrue();
});

it('missing name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('measurement-units.store'), [
            'name' => '',
            'abbreviation' => 'KG',
        ])
        ->assertSessionHasErrors(['name']);
});

it('missing abbreviation returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('measurement-units.store'), [
            'name' => 'Kilograms',
            'abbreviation' => '',
        ])
        ->assertSessionHasErrors(['abbreviation']);
});

it('name exceeding 100 chars returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('measurement-units.store'), [
            'name' => str_repeat('a', 101),
            'abbreviation' => 'KG',
        ])
        ->assertSessionHasErrors(['name']);
});

it('abbreviation exceeding 10 chars returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    actingAs($admin)
        ->post(route('measurement-units.store'), [
            'name' => 'Kilograms',
            'abbreviation' => str_repeat('a', 11),
        ])
        ->assertSessionHasErrors(['abbreviation']);
});

it('duplicate name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    MeasurementUnit::factory()->create(['name' => 'Kilograms']);

    actingAs($admin)
        ->post(route('measurement-units.store'), [
            'name' => 'Kilograms',
            'abbreviation' => 'XX',
        ])
        ->assertSessionHasErrors(['name']);
});

it('duplicate abbreviation returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    MeasurementUnit::factory()->create(['abbreviation' => 'KG']);

    actingAs($admin)
        ->post(route('measurement-units.store'), [
            'name' => 'NewUnit',
            'abbreviation' => 'KG',
        ])
        ->assertSessionHasErrors(['abbreviation']);
});

// ─── Update ───────────────────────────────────────────────────────────────────

it('admin updates a measurement unit', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $unit = MeasurementUnit::factory()->create(['name' => 'Old', 'abbreviation' => 'OL']);

    actingAs($admin)
        ->put(route('measurement-units.update', $unit), [
            'name' => 'New',
            'abbreviation' => 'NW',
        ])
        ->assertRedirect(route('measurement-units'));

    $unit->refresh();
    expect($unit->name)->toBe('New');
    expect($unit->abbreviation)->toBe('NW');
});

it('updating with the same name and abbreviation passes unique rule', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $unit = MeasurementUnit::factory()->create(['name' => 'Kilograms', 'abbreviation' => 'KG']);

    actingAs($admin)
        ->put(route('measurement-units.update', $unit), [
            'name' => 'Kilograms',
            'abbreviation' => 'KG',
        ])
        ->assertRedirect(route('measurement-units'));

    $unit->refresh();
    expect($unit->name)->toBe('Kilograms');
});

it('renaming to an existing name returns validation error', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    MeasurementUnit::factory()->create(['name' => 'Taken']);
    $unit = MeasurementUnit::factory()->create(['name' => 'Other']);

    actingAs($admin)
        ->put(route('measurement-units.update', $unit), [
            'name' => 'Taken',
            'abbreviation' => 'OT',
        ])
        ->assertSessionHasErrors(['name']);
});

// ─── Soft Delete ──────────────────────────────────────────────────────────────

it('admin deletes a measurement unit', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $unit = MeasurementUnit::factory()->create();

    actingAs($admin)
        ->delete(route('measurement-units.destroy', $unit))
        ->assertRedirect(route('measurement-units'));

    assertSoftDeleted($unit);
});

it('measurement unit with active products cannot be deleted', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $unit = MeasurementUnit::factory()->create();
    $brand = Brand::factory()->create();
    Product::factory()->create(['measurement_unit_id' => $unit->id, 'brand_id' => $brand->id]);

    actingAs($admin)
        ->delete(route('measurement-units.destroy', $unit))
        ->assertRedirect()
        ->assertSessionHas('error');

    $unit->refresh();
    expect($unit->deleted_at)->toBeNull();
});

// ─── Restore ──────────────────────────────────────────────────────────────────

it('admin restores a soft-deleted measurement unit', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    $unit = MeasurementUnit::factory()->create();
    $unit->delete();

    assertSoftDeleted($unit);

    actingAs($admin)
        ->put(route('measurement-units.restore', $unit->id))
        ->assertRedirect(route('measurement-units'));

    $unit->refresh();
    expect($unit->deleted_at)->toBeNull();
});

// ─── Permission Denials ──────────────────────────────────────────────────────

it('non-admin cannot create measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    actingAs($user)
        ->post(route('measurement-units.store'), ['name' => 'Test', 'abbreviation' => 'TS'])
        ->assertForbidden();
});

it('non-admin cannot update measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $unit = MeasurementUnit::factory()->create();

    actingAs($user)
        ->put(route('measurement-units.update', $unit), ['name' => 'Test', 'abbreviation' => 'TS'])
        ->assertForbidden();
});

it('non-admin cannot delete measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $unit = MeasurementUnit::factory()->create();

    actingAs($user)
        ->delete(route('measurement-units.destroy', $unit))
        ->assertForbidden();
});

it('non-admin cannot restore measurement units', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::SALESMAN);

    $unit = MeasurementUnit::factory()->create();
    $unit->delete();

    actingAs($user)
        ->put(route('measurement-units.restore', $unit->id))
        ->assertForbidden();
});

// ─── Migration Bug Guard ─────────────────────────────────────────────────────

it('migration uses measurement_units not measure_units in down method', function () {
    $files = glob(database_path('migrations/*_create_measurement_units_table.php'));
    expect($files)->not->toBeFalse('Measurement units migration glob failed')
        ->and($files)->not->toBeEmpty('Measurement units migration file not found');

    $file = is_array($files) ? $files[0] : '';
    $content = file_get_contents($file);

    expect($content)->not->toContain("'measure_units'")
        ->and($content)->toContain("'measurement_units'");
});
