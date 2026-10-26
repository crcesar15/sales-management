<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo(PermissionsEnum::POS_ACCESS->value);
});

it('renders the POS customer list for a user with customer view permission', function (): void {
    $this->user->givePermissionTo(PermissionsEnum::CUSTOMERS_VIEW->value);
    $customer = Customer::factory()->create(['first_name' => 'Ada']);

    $this->actingAs($this->user)
        ->get(route('pos.customers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Pos/Customers/Index')
            ->where('customers.data.0.id', $customer->id)
            ->where('filters.status', 'active'));
});

it('forbids POS customer access without customer view permission', function (): void {
    $this->actingAs($this->user)
        ->getJson(route('pos.customers.index'))
        ->assertForbidden();
});

it('filters and sorts POS customers', function (): void {
    $this->user->givePermissionTo(PermissionsEnum::CUSTOMERS_VIEW->value);
    $matchingCustomer = Customer::factory()->create(['phone' => '111', 'status' => 'active']);
    Customer::factory()->create(['phone' => '999', 'status' => 'inactive']);

    $this->actingAs($this->user)
        ->get(route('pos.customers.index', ['filter' => $matchingCustomer->tax_id, 'status' => 'active', 'order_by' => 'phone', 'order_direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('customers.data.0.id', $matchingCustomer->id)
            ->where('filters.order_by', 'phone')
            ->where('filters.order_direction', 'desc'));
});

it('creates a customer from POS and returns to the POS customer list', function (): void {
    $this->user->givePermissionTo([PermissionsEnum::CUSTOMERS_VIEW->value, PermissionsEnum::CUSTOMERS_CREATE->value]);

    $this->actingAs($this->user)
        ->post(route('pos.customers.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.test',
            'phone' => '123',
            'tax_id' => '1234567890',
            'tax_id_name' => 'Ada Lovelace',
            'status' => 'active',
        ])
        ->assertRedirect(route('pos.customers.index'));

    $this->assertDatabaseHas('customers', ['tax_id' => '1234567890']);
});

it('validates customer creation from POS', function (): void {
    $this->user->givePermissionTo(PermissionsEnum::CUSTOMERS_CREATE->value);

    $this->actingAs($this->user)
        ->post(route('pos.customers.store'), [])
        ->assertSessionHasErrors(['tax_id', 'tax_id_name', 'status']);
});

it('updates a customer from POS', function (): void {
    $this->user->givePermissionTo(PermissionsEnum::CUSTOMERS_EDIT->value);
    $customer = Customer::factory()->create(['tax_id_name' => 'Before']);

    $this->actingAs($this->user)
        ->put(route('pos.customers.update', $customer), [
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'tax_id' => $customer->tax_id,
            'tax_id_name' => 'After',
            'status' => 'active',
        ])
        ->assertRedirect(route('pos.customers.index'));

    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'tax_id_name' => 'After']);
});

it('deletes a customer from POS', function (): void {
    $this->user->givePermissionTo(PermissionsEnum::CUSTOMERS_DELETE->value);
    $customer = Customer::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('pos.customers.destroy', $customer))
        ->assertRedirect(route('pos.customers.index'));

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

it('does not delete a customer with sales orders from POS', function (): void {
    $this->user->givePermissionTo(PermissionsEnum::CUSTOMERS_DELETE->value);
    $customer = Customer::factory()->create();
    SalesOrder::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($this->user)
        ->delete(route('pos.customers.destroy', $customer))
        ->assertSessionHasErrors('customer');

    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});
