<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\CashRegister;
use App\Models\CashRegisterMovement;
use App\Models\CashRegisterShift;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderPayment;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\CarbonImmutable;
use Database\Seeders\SettingsSeeder;
use Inertia\Inertia;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-08 14:00:00', 'UTC'));
    $this->seed(SettingsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);
    $this->store = Store::factory()->create(['status' => 'active']);
});

/** @param array<string, mixed> $overrides */
function recordedDashboardSale(Store $store, User $user, ?string $cost = '6', array $overrides = []): SalesOrder
{
    $order = SalesOrder::factory()->create([
        'store_id' => $store->id, 'user_id' => $user->id, 'status' => 'fulfilled', 'payment_status' => 'pending',
        'fulfilled_at' => now()->subHour(), 'sub_total' => 100, 'total' => 100, 'tax_amount' => 0, ...$overrides,
    ]);
    $variant = ProductVariant::factory()->create(['product_id' => Product::factory()->create(['status' => 'active']), 'status' => 'active']);
    $item = SalesOrderItem::factory()->create(['sales_order_id' => $order->id, 'product_variant_id' => $variant->id,
        'quantity' => 10, 'unit_price' => 10, 'line_total' => 100, 'net_sales_amount' => 100]);
    $batch = Batch::factory()->create(['product_variant_id' => $variant->id, 'store_id' => $store->id, 'unit_cost' => $cost,
        'status' => 'active', 'expiry_date' => null]);
    $item->stockAllocations()->create(['batch_id' => $batch->id, 'quantity' => 10, 'unit_cost' => $cost]);

    return $order;
}

it('recognizes credit sales at fulfillment and separates later collections without duplicate joins', function () {
    $order = recordedDashboardSale($this->store, $this->admin);
    SalesOrderPayment::factory()->create(['sales_order_id' => $order->id, 'amount' => 30, 'created_at' => now()->subMinutes(20)]);
    SalesOrderPayment::factory()->create(['sales_order_id' => $order->id, 'amount' => 20, 'created_at' => now()->subMinutes(10)]);
    $snapshot = app(DashboardService::class)->snapshot($this->admin, []);
    expect($snapshot['sales']['net_sales'])->toBe(100.0)
        ->and($snapshot['sales']['order_count'])->toBe(1)
        ->and($snapshot['sales']['profitability']['gross_profit'])->toBe(40.0)
        ->and($snapshot['sales']['profitability']['margin'])->toBe(40.0)
        ->and($snapshot['collections']['total'])->toBe(50.0);
    $order->items()->first()->productVariant->update(['purchase_price' => 50]);
    $this->travel(1)->days();
    $order->update(['status' => 'completed', 'paid_at' => now()]);
    SalesOrderPayment::factory()->create(['sales_order_id' => $order->id, 'amount' => 50, 'created_at' => now()->subMinute()]);
    $next = app(DashboardService::class)->snapshot($this->admin, []);
    expect($next['sales']['net_sales'])->toBe(0.0)->and($next['collections']['total'])->toBe(50.0);
    $historic = app(DashboardService::class)->snapshot($this->admin, ['preset' => 'custom', 'from' => '2026-11-08', 'to' => '2026-11-08']);
    expect($historic['sales']['profitability']['gross_profit'])->toBe(40.0);
});

it('does not turn unknown costs into profit and accepts genuinely zero cost stock', function () {
    recordedDashboardSale($this->store, $this->admin, null);
    recordedDashboardSale($this->store, $this->admin, '0');
    $profit = app(DashboardService::class)->snapshot($this->admin, [])['sales']['profitability'];
    expect($profit['complete'])->toBeFalse()->and($profit['gross_profit'])->toBeNull()
        ->and($profit['missing_lines'])->toBe(1)->and($profit['known_profit'])->toBe(100.0);
});

it('limits personal sales and never expands store scope when profit permission is granted', function () {
    $user = User::factory()->create();
    $user->stores()->attach($this->store);
    $user->givePermissionTo(PermissionsEnum::SALES_VIEW, PermissionsEnum::DASHBOARD_PROFIT_VIEW);
    $otherStore = Store::factory()->create();
    recordedDashboardSale($this->store, $user);
    recordedDashboardSale($this->store, $this->admin);
    recordedDashboardSale($otherStore, $user);
    $snapshot = app(DashboardService::class)->snapshot($user, []);
    expect($snapshot['sales']['net_sales'])->toBe(100.0)->and($snapshot['store_count'])->toBe(1);
    $this->actingAs($user)->getJson(route('home', ['store_id' => $otherStore->id]))->assertForbidden();
    $this->actingAs($user)->getJson(route('sales-orders.show', SalesOrder::where('store_id', $otherStore->id)->first()))->assertForbidden();
});

it('replaces restricted financial data on a partial refresh after permission revocation', function () {
    $user = User::factory()->create();
    $user->stores()->attach($this->store);
    $user->givePermissionTo(PermissionsEnum::SALES_VIEW, PermissionsEnum::DASHBOARD_PROFIT_VIEW);
    recordedDashboardSale($this->store, $user);
    $this->actingAs($user)->get(route('home'))->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')->where('dashboard.capabilities.profit', true));
    $user->revokePermissionTo(PermissionsEnum::DASHBOARD_PROFIT_VIEW);
    $this->actingAs($user->fresh())->get(route('home'), [
        'X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'Dashboard/Index', 'X-Inertia-Partial-Data' => 'dashboard',
        'X-Inertia-Version' => Inertia::getVersion(),
    ])->assertSuccessful()->assertJsonPath('props.dashboard.sales.profitability', null)
        ->assertJsonPath('props.dashboard.capabilities.profit', false);
});

it('uses same weekday and same time comparisons and business timezone boundaries', function () {
    recordedDashboardSale($this->store, $this->admin, '6', ['fulfilled_at' => '2026-11-01 13:00:00']);
    recordedDashboardSale($this->store, $this->admin, '6', ['fulfilled_at' => '2026-11-01 15:00:00']);
    $snapshot = app(DashboardService::class)->snapshot($this->admin, []);
    expect($snapshot['comparison']['net_sales'])->toBe(100.0);
    Setting::set('timezone', 'America/La_Paz');
    recordedDashboardSale($this->store, $this->admin, '6', ['fulfilled_at' => '2026-11-08 03:59:59']);
    recordedDashboardSale($this->store, $this->admin, '6', ['fulfilled_at' => '2026-11-08 04:00:00']);
    expect(app(DashboardService::class)->snapshot($this->admin, [])['sales']['net_sales'])->toBe(100.0);
});

it('retains store-specific stockouts even when another store has surplus stock', function () {
    $otherStore = Store::factory()->create(['status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => Product::factory()->create(['status' => 'active']), 'minimum_stock_level' => 10, 'status' => 'active']);
    Batch::factory()->create(['product_variant_id' => $variant->id, 'store_id' => $this->store->id, 'remaining_quantity' => 0, 'status' => 'closed']);
    Batch::factory()->create(['product_variant_id' => $variant->id, 'store_id' => $otherStore->id, 'remaining_quantity' => 100, 'status' => 'active', 'expiry_date' => null]);
    $snapshot = app(DashboardService::class)->snapshot($this->admin, []);
    expect($snapshot['inventory']['stockouts'])->toBe(1)->and($snapshot['inventory']['stock_items'][0]['store_id'])->toBe($this->store->id);
    $this->actingAs($this->admin)->get(route('inventory.alerts', ['per_store' => 1]))->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('lowStockAlerts.meta.total', 1));
    $otherStore->update(['status' => 'inactive']);
    $this->get(route('inventory.alerts', ['per_store' => 1, 'store_id' => $otherStore->id]))->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('lowStockAlerts.meta.total', 0)->where('expiryAlerts.meta.total', 0));
});

it('validates custom ranges and safely handles users with no assigned stores', function () {
    $this->actingAs($this->admin)->getJson(route('home', ['preset' => 'custom', 'from' => '2026-11-09', 'to' => '2026-11-10']))->assertUnprocessable();
    $this->getJson(route('home', ['preset' => 'custom', 'from' => '2024-01-01', 'to' => '2026-01-01']))->assertUnprocessable();
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::SALES_VIEW_ALL, PermissionsEnum::SALES_VIEW);
    recordedDashboardSale($this->store, $this->admin);
    $empty = app(DashboardService::class)->snapshot($user, []);
    expect($empty['sales']['net_sales'])->toBe(0.0)->and($empty['sales']['average_sale'])->toBeNull()->and($empty['store_count'])->toBe(0);
});

it('reconciles the sales drill-down with fulfillment dates and allows read-only credit-sale inspection', function () {
    $sale = recordedDashboardSale($this->store, $this->admin, '6', ['created_at' => now()->subDays(2)]);
    recordedDashboardSale($this->store, $this->admin, '6', ['fulfilled_at' => now()->addHour()]);
    $this->actingAs($this->admin)->get(route('sales-orders', [
        'store_id' => $this->store->id, 'date_basis' => 'fulfilled', 'from' => '2026-11-08',
        'to' => '2026-11-08', 'report_to' => '2026-11-08T14:00:00Z',
    ]))->assertSuccessful()->assertInertia(fn ($page) => $page->where('orders.meta.total', 1)->where('orders.data.0.id', $sale->id));
    $reader = User::factory()->create();
    $reader->stores()->attach($this->store);
    $reader->givePermissionTo(PermissionsEnum::SALES_VIEW, PermissionsEnum::SALES_VIEW_ALL);
    $this->actingAs($reader)->get(route('sales-orders.show', $sale))->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('SalesOrders/Show/Index'));
});

it('separates shift shortages and overages and calculates current expected cash', function () {
    $register = CashRegister::factory()->create(['store_id' => $this->store->id]);
    foreach ([-10, 7] as $difference) {
        CashRegisterShift::factory()->create(['cash_register_id' => $register->id,
            'status' => $difference < 0 ? 'forced_close' : 'closed', 'closed_at' => now()->subHour(), 'difference' => $difference]);
    }
    $open = CashRegisterShift::factory()->create(['cash_register_id' => $register->id, 'opening_balance' => 100]);
    CashRegisterMovement::factory()->create(['cash_register_shift_id' => $open->id, 'type' => 'cash_in', 'amount' => 20]);
    CashRegisterMovement::factory()->create(['cash_register_shift_id' => $open->id, 'type' => 'cash_out', 'amount' => 5]);
    $order = recordedDashboardSale($this->store, $this->admin);
    SalesOrderPayment::factory()->create(['sales_order_id' => $order->id, 'cash_register_shift_id' => $open->id, 'payment_method' => 'cash', 'amount' => 30]);
    $shifts = app(DashboardService::class)->snapshot($this->admin, [])['shifts'];
    expect($shifts['shortages'])->toBe(10.0)->and($shifts['overages'])->toBe(7.0)
        ->and($shifts['open_count'])->toBe(1)->and($shifts['open'][0]['expected_cash'])->toBe(145.0);
    $this->actingAs($this->admin)->get(route('shifts', ['date_basis' => 'closed', 'date_from' => '2026-11-08', 'date_to' => '2026-11-08']))
        ->assertSuccessful()->assertInertia(fn ($page) => $page->where('shifts.meta.total', 2));
});

it('flags missing costs even when a fully discounted sale has no revenue', function () {
    $order = recordedDashboardSale($this->store, $this->admin, null, ['sub_total' => 100, 'discount' => 100, 'total' => 0]);
    $order->items()->update(['net_sales_amount' => 0]);
    $profit = app(DashboardService::class)->snapshot($this->admin, [])['sales']['profitability'];
    expect($profit['complete'])->toBeFalse()->and($profit['gross_profit'])->toBeNull()->and($profit['margin'])->toBeNull();
});

it('counts both repeated daylight-saving hours once in the business day', function () {
    Setting::set('timezone', 'America/New_York');
    $this->travelTo(CarbonImmutable::parse('2026-11-01 07:30:00', 'UTC'));
    recordedDashboardSale($this->store, $this->admin, '6', ['fulfilled_at' => '2026-11-01 05:30:00']);
    recordedDashboardSale($this->store, $this->admin, '6', ['fulfilled_at' => '2026-11-01 06:30:00']);
    $snapshot = app(DashboardService::class)->snapshot($this->admin, []);
    expect($snapshot['sales']['net_sales'])->toBe(200.0)
        ->and(array_sum(array_column($snapshot['trend']['points'], 'sales')))->toBe(200.0);
});
