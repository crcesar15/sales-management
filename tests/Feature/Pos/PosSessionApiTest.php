<?php

declare(strict_types=1);

use App\Enums\CashRegisterShiftStatus;
use App\Enums\CashRegisterStatus;
use App\Enums\PermissionsEnum;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Store;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->cashier = User::factory()->create();
    $this->cashier->givePermissionTo([
        PermissionsEnum::POS_ACCESS->value,
        PermissionsEnum::SHIFTS_OPEN->value,
    ]);
});

it('returns the authenticated cashiers open POS shift', function () {
    $store = Store::factory()->create();
    $register = CashRegister::factory()->create(['store_id' => $store->id]);
    $shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::OPEN->value,
    ]);

    actingAs($this->cashier, 'sanctum')
        ->getJson(route('api.v1.pos.session'))
        ->assertSuccessful()
        ->assertJsonPath('shift.id', $shift->id)
        ->assertJsonPath('register.id', $register->id)
        ->assertJsonPath('store.id', $store->id);
});

it('lists only available registers in the cashiers assigned stores', function () {
    $assignedStore = Store::factory()->create();
    $unassignedStore = Store::factory()->create();
    $this->cashier->stores()->attach($assignedStore);

    $availableRegister = CashRegister::factory()->create(['store_id' => $assignedStore->id]);
    $inactiveRegister = CashRegister::factory()->create([
        'store_id' => $assignedStore->id,
        'status' => CashRegisterStatus::INACTIVE->value,
    ]);
    $occupiedRegister = CashRegister::factory()->create(['store_id' => $assignedStore->id]);
    $unassignedRegister = CashRegister::factory()->create(['store_id' => $unassignedStore->id]);
    CashRegisterShift::factory()->create(['cash_register_id' => $occupiedRegister->id]);

    actingAs($this->cashier, 'sanctum')
        ->getJson(route('api.v1.pos.registers'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $availableRegister->id)
        ->assertJsonMissing(['id' => $inactiveRegister->id])
        ->assertJsonMissing(['id' => $occupiedRegister->id])
        ->assertJsonMissing(['id' => $unassignedRegister->id]);
});

it('opens a shift on an available assigned register', function () {
    $store = Store::factory()->create();
    $register = CashRegister::factory()->create(['store_id' => $store->id]);
    $this->cashier->stores()->attach($store);

    actingAs($this->cashier, 'sanctum')
        ->postJson(route('api.v1.pos.session.shift.open'), [
            'register_id' => $register->id,
            'opening_balance' => 100,
        ])
        ->assertCreated()
        ->assertJsonPath('register.id', $register->id)
        ->assertJsonPath('shift.cash_register_id', $register->id)
        ->assertJsonPath('shift.user_id', $this->cashier->id);

    $this->assertDatabaseHas('cash_register_shifts', [
        'cash_register_id' => $register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::OPEN->value,
        'opening_balance' => 100,
    ]);
});

it('rejects opening a shift on an occupied register', function () {
    $store = Store::factory()->create();
    $register = CashRegister::factory()->create(['store_id' => $store->id]);
    $this->cashier->stores()->attach($store);
    CashRegisterShift::factory()->create(['cash_register_id' => $register->id]);

    actingAs($this->cashier, 'sanctum')
        ->postJson(route('api.v1.pos.session.shift.open'), [
            'register_id' => $register->id,
            'opening_balance' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'A shift is already open on this register.');
});

it('rejects opening another shift for a cashier with an open shift', function () {
    $store = Store::factory()->create();
    $this->cashier->stores()->attach($store);
    $currentRegister = CashRegister::factory()->create(['store_id' => $store->id]);
    $availableRegister = CashRegister::factory()->create(['store_id' => $store->id]);
    CashRegisterShift::factory()->create([
        'cash_register_id' => $currentRegister->id,
        'user_id' => $this->cashier->id,
    ]);

    actingAs($this->cashier, 'sanctum')
        ->postJson(route('api.v1.pos.session.shift.open'), [
            'register_id' => $availableRegister->id,
            'opening_balance' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'This cashier already has an open shift.');
});

it('does not allow opening a shift on a register outside the cashiers assigned stores', function () {
    $register = CashRegister::factory()->create();

    actingAs($this->cashier, 'sanctum')
        ->postJson(route('api.v1.pos.session.shift.open'), [
            'register_id' => $register->id,
            'opening_balance' => 0,
        ])
        ->assertNotFound();
});

it('forbids POS API access without the required permissions', function () {
    $user = User::factory()->create();

    actingAs($user, 'sanctum')
        ->getJson(route('api.v1.pos.registers'))
        ->assertForbidden();
});
