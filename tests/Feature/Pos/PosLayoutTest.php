<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

it('renders POS page for authorized user', function () {
    $user = User::factory()->create();
    $user->assignRole(RolesEnum::ADMIN);

    $response = actingAs($user)
        ->get(route('pos'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($assert) => $assert->component('Pos/Index'));
});

it('forbids POS access without pos.access permission', function () {
    $user = User::factory()->create();
    // Create a SALESMAN role without pos.access permission
    $salesmanRole = Spatie\Permission\Models\Role::firstOrCreate(['name' => RolesEnum::SALESMAN->value]);
    $user->assignRole($salesmanRole);

    actingAs($user)
        ->get(route('pos'))
        ->assertForbidden();
});

it('redirects guest to login', function () {
    $this->get(route('pos'))
        ->assertRedirect(route('login'));
});
