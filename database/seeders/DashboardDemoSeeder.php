<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\CashRegister;
use App\Models\CashRegisterShift;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderPayment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DashboardDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (SalesOrder::where('notes', 'Synthetic dashboard demonstration')->exists()) {
            return;
        }
        $admin = User::role(RolesEnum::ADMIN->value)->firstOrFail();
        DB::transaction(function () use ($admin): void {
            $variants = collect([
                ['Demo · Orange juice 1 L', 'DEMO-JUICE', 15, 9],
                ['Demo · Rice 1 kg', 'DEMO-RICE', 12, 7],
                ['Demo · Household cleaner', 'DEMO-CLEAN', 24, 14],
            ])->map(fn (array $item) => ProductVariant::factory()->create([
                'product_id' => Product::factory()->create(['name' => $item[0], 'status' => 'active'])->id,
                'identifier' => $item[1], 'price' => $item[2], 'purchase_price' => $item[3],
                'minimum_stock_level' => 10, 'status' => 'active',
            ]));
            foreach (['DEMO-CENTRE' => 'Demo · Centre', 'DEMO-NORTH' => 'Demo · North'] as $code => $name) {
                $store = Store::factory()->create(['code' => $code, 'name' => $name, 'status' => 'active']);
                $admin->stores()->syncWithoutDetaching([$store->id]);
                $register = CashRegister::factory()->create(['store_id' => $store->id, 'name' => 'Demo register']);
                $shift = CashRegisterShift::factory()->create(['cash_register_id' => $register->id, 'user_id' => $admin->id,
                    'status' => 'open', 'opening_balance' => 100, 'opened_at' => now()->startOfDay()]);
                foreach ($variants as $index => $variant) {
                    $batch = Batch::factory()->create(['product_variant_id' => $variant->id, 'store_id' => $store->id,
                        'reception_order_id' => null, 'unit_cost' => $variant->purchase_price, 'cost_source' => 'opening_inventory',
                        'initial_quantity' => 1000, 'remaining_quantity' => 1000, 'sold_quantity' => 0,
                        'missing_quantity' => 0, 'transferred_quantity' => 0, 'status' => 'active', 'expiry_date' => $index === 0 ? today()->addDays(4) : null]);
                    foreach (range(0, 13) as $day) {
                        $quantity = 2 + (($day + $index) % 5);
                        $total = $quantity * (float) $variant->price;
                        $at = $day === 0 ? now()->subSeconds(30 + $index * 60) : today()->subDays($day)->setTime(10 + $index, 20);
                        $order = SalesOrder::factory()->create(['store_id' => $store->id, 'user_id' => $admin->id,
                            'status' => 'completed', 'payment_status' => 'paid', 'fulfilled_at' => $at, 'paid_at' => $at,
                            'completed_at' => $at, 'sub_total' => $total, 'total' => $total, 'tax_amount' => 0,
                            'cash_register_shift_id' => $day === 0 ? $shift->id : null,
                            'notes' => 'Synthetic dashboard demonstration']);
                        $item = $order->items()->create(['product_variant_id' => $variant->id, 'quantity' => $quantity,
                            'unit_price' => $variant->price, 'line_discount' => 0, 'conversion_factor' => 1,
                            'line_total' => $total, 'net_sales_amount' => $total]);
                        $item->stockAllocations()->create(['batch_id' => $batch->id, 'quantity' => $quantity, 'unit_cost' => $batch->unit_cost]);
                        SalesOrderPayment::factory()->create(['sales_order_id' => $order->id, 'user_id' => $admin->id,
                            'cash_register_shift_id' => $day === 0 ? $shift->id : null,
                            'amount' => $total, 'payment_method' => $index === 1 ? 'qr' : 'cash', 'created_at' => $at, 'updated_at' => $at]);
                        $batch->decrement('remaining_quantity', $quantity);
                        $batch->increment('sold_quantity', $quantity);
                    }
                }
                CashRegisterShift::factory()->create(['cash_register_id' => $register->id, 'user_id' => $admin->id,
                    'status' => 'closed', 'opened_at' => now()->subHours(3), 'closed_at' => now()->subMinute(),
                    'opening_balance' => 100, 'expected_closing' => 100, 'closing_balance' => 95, 'difference' => -5,
                    'discrepancy_reason' => 'Synthetic demonstration shortage']);
                Batch::factory()->create(['product_variant_id' => $variants->sole(fn (ProductVariant $variant): bool => $variant->identifier === 'DEMO-CLEAN')->id, 'store_id' => $store->id,
                    'reception_order_id' => null, 'unit_cost' => 14, 'initial_quantity' => 5, 'remaining_quantity' => 5,
                    'status' => 'active', 'expiry_date' => today()->subDay()]);
            }
            foreach ($variants as $variant) {
                $variant->recalculateStock();
            }
        });
    }
}
