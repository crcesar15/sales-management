<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run Role Seeder
        $this->call(RoleSeeder::class);

        // Admin user
        $admin = User::factory()->create(
            [
                'first_name' => 'Cesar',
                'last_name' => 'Rodriguez',
                'email' => 'crcesar15@gmail.com',
                'username' => 'admin',
                'phone' => '123456789',
                'status' => 'ACTIVE',
                'date_of_birth' => '1990-01-01',
                'password' => bcrypt('admin'),
            ]
        );

        $admin->assignRole(['Super Administrator']);

        // Run settings and permissions seeder
        $this->call(SettingsSeeder::class);
        $this->call(PermissionSeeder::class);

        // Create 10 Salesman users
        User::factory(10)->create()->each(function ($user): void {
            $user->assignRole(['Salesman']);
        });

        // Create 10 Brands
        Brand::factory(10)->create();

        // Create 10 Measurement Units
        MeasurementUnit::factory(10)->create();

        // Create 10 Categories, each with 5 products
        Category::factory(10)->create();

        // create 50 products
        $products = [];

        for ($i = 0; $i < 50; $i++) {
            $products[] = Product::factory()
                ->create([
                    'measurement_unit_id' => MeasurementUnit::all()->random()->id,
                    'brand_id' => Brand::all()->random()->id,
                ]);
        }

        foreach ($products as $product) {
            // attach categories to products
            $product->categories()->attach(
                Category::all()->random()->id
            );

            // create 1 to 3 variants for each product
            $numberOfVariants = random_int(1, 3);

            if ($numberOfVariants === 1) {
                // create a single variant without options
                $product->variants()->create([
                    'identifier' => 'SKU-'
                            . mb_strtoupper(mb_substr($product->name, 0, 3))
                            . '-'
                            . random_int(1000, 9999),
                    'price' => random_int(100, 1000),
                    'stock' => random_int(10, 100),
                ]);

                continue;
            }

            // create 1 options for each product
            $options = ProductOption::factory(1)
                ->create([
                    'product_id' => $product->id,
                ]);

            foreach ($options as $option) {
                // create values for each option
                ProductOptionValue::factory($numberOfVariants)
                    ->create([
                        'product_option_id' => $option->id,
                    ])->each(function ($value) use ($product): void {
                        // create product variants for each option value
                        $product->variants()->create([
                            'identifier' => 'SKU-'
                                    . mb_strtoupper(mb_substr($product->name, 0, 3))
                                    . '-'
                                    . mb_strtoupper(mb_substr($value->value, 0, 3))
                                    . '-'
                                    . random_int(1000, 9999),
                            'price' => random_int(100, 1000),
                            'stock' => random_int(10, 100),
                        ])->values()->attach($value->id);
                    });
            }
        }

        // Create 10 vendors
        Vendor::factory(10)->create();

        // Create 10 catalogs
        Catalog::factory(10)->create(
            [
                'vendor_id' => Vendor::all()->random()->id,
                'product_variant_id' => ProductVariant::all()->random()->id,
            ]
        );

        // Create 10 purchase orders
        PurchaseOrder::factory(10)->create(
            [
                'user_id' => User::all()->random()->id,
                'vendor_id' => Vendor::all()->random()->id,
            ]
        )->each(function (PurchaseOrder $purchaseOrder): void {
            $purchaseOrder->products()->attach(Product::all()->random()->id, [
                'quantity' => random_int(1, 10),
                'price' => random_int(100, 1000),
                'total' => random_int(100, 1000),
            ]);
        });
    }
}
