<?php

declare(strict_types=1);

use App\Enums\CashRegisterStatus;
use App\Enums\CashRegisterShiftStatus;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Store;
use App\Models\User;
use App\Services\CashRegisterShiftService;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->service = app(CashRegisterShiftService::class);

    $this->store = Store::factory()->create();
    $this->cashier = User::factory()->create();
    $this->manager = User::factory()->create();

    $this->register = CashRegister::factory()->create([
        'store_id' => $this->store->id,
        'status' => CashRegisterStatus::ACTIVE->value,
    ]);

    actingAs($this->cashier);
});

it('allows closing an open shift (open -> closed)', function () {
    $shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::OPEN->value,
        'opening_balance' => 100,
    ]);

    $closed = $this->service->closeShift($shift, 100);

    expect($closed->status)->toBe(CashRegisterShiftStatus::CLOSED)
        ->and($closed->closing_balance)->toBe('100.00')
        ->and($closed->closed_at)->not->toBeNull();
});

it('allows force-closing an open shift (open -> forced_close)', function () {
    $shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::OPEN->value,
        'opening_balance' => 50,
    ]);

    $closed = $this->service->forceCloseShift($shift, $this->manager, 50);

    expect($closed->status)->toBe(CashRegisterShiftStatus::FORCED_CLOSE)
        ->and($closed->closed_at)->not->toBeNull();
});

it('rejects closing an already-closed shift (closed -> closed)', function () {
    $shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::CLOSED->value,
        'opening_balance' => 100,
        'closing_balance' => 100,
        'expected_closing' => 100,
        'difference' => 0,
        'closed_at' => now(),
    ]);

    $this->service->closeShift($shift, 100);
})->throws(InvalidArgumentException::class, 'Cannot transition shift from closed to closed');

it('rejects force-closing a closed shift (closed -> forced_close)', function () {
    $shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::CLOSED->value,
        'opening_balance' => 100,
        'closing_balance' => 100,
        'expected_closing' => 100,
        'difference' => 0,
        'closed_at' => now(),
    ]);

    $this->service->forceCloseShift($shift, $this->manager, 100);
})->throws(InvalidArgumentException::class, 'Cannot transition shift from closed to forced_close');

it('rejects adding a movement to a closed shift (closed -> open)', function () {
    $shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::CLOSED->value,
        'opening_balance' => 100,
        'closing_balance' => 100,
        'expected_closing' => 100,
        'difference' => 0,
        'closed_at' => now(),
    ]);

    $this->service->addMovement($shift, 'cash_in', 50, 'should fail', $this->cashier);
})->throws(InvalidArgumentException::class, 'Cannot transition shift from closed to open');

it('rejects any transition from a forced-closed shift', function () {
    $shift = CashRegisterShift::factory()->create([
        'cash_register_id' => $this->register->id,
        'user_id' => $this->cashier->id,
        'status' => CashRegisterShiftStatus::FORCED_CLOSE->value,
        'opening_balance' => 100,
        'closing_balance' => 100,
        'expected_closing' => 100,
        'difference' => 0,
        'closed_at' => now(),
    ]);

    $this->service->closeShift($shift, 100);
})->throws(InvalidArgumentException::class, 'Cannot transition shift from forced_close to closed');