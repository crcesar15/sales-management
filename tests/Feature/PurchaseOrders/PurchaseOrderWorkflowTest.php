<?php

declare(strict_types=1);

use App\Enums\PurchaseOrderStatus;
use App\Enums\RolesEnum;
use App\Models\PurchaseOrder;
use App\Models\User;

use function Pest\Laravel\actingAs;

function purchaseOrderWorkflowAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(RolesEnum::ADMIN);

    return $admin;
}

it('approves a draft purchase order directly', function () {
    $admin = purchaseOrderWorkflowAdmin();
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::DRAFT]);

    actingAs($admin)
        ->patch(route('purchase-orders.approve', $purchaseOrder))
        ->assertRedirect();

    expect($purchaseOrder->fresh()?->status)->toBe(PurchaseOrderStatus::APPROVED);
});

it('requires completion notes when closing an incompletely received purchase order', function () {
    $admin = purchaseOrderWorkflowAdmin();
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::APPROVED]);

    actingAs($admin)
        ->from(route('purchase-orders.show', $purchaseOrder))
        ->patch(route('purchase-orders.close', $purchaseOrder))
        ->assertRedirect(route('purchase-orders.show', $purchaseOrder))
        ->assertSessionHasErrors('status');

    expect($purchaseOrder->fresh()?->status)->toBe(PurchaseOrderStatus::APPROVED);
});

it('closes an incompletely received purchase order with a reason', function () {
    $admin = purchaseOrderWorkflowAdmin();
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::APPROVED]);

    actingAs($admin)
        ->patch(route('purchase-orders.close', $purchaseOrder), ['completion_notes' => 'Vendor cannot supply the remaining items.'])
        ->assertRedirect();

    $purchaseOrder->refresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::CLOSED)
        ->and($purchaseOrder->completion_notes)->toBe('Vendor cannot supply the remaining items.');
});

it('does not cancel a paid purchase order', function () {
    $admin = purchaseOrderWorkflowAdmin();
    $purchaseOrder = PurchaseOrder::factory()->create([
        'status' => PurchaseOrderStatus::APPROVED,
        'is_paid' => true,
    ]);

    actingAs($admin)
        ->from(route('purchase-orders.show', $purchaseOrder))
        ->patch(route('purchase-orders.cancel', $purchaseOrder))
        ->assertRedirect(route('purchase-orders.show', $purchaseOrder))
        ->assertSessionHasErrors('status');

    expect($purchaseOrder->fresh()?->status)->toBe(PurchaseOrderStatus::APPROVED);
});
