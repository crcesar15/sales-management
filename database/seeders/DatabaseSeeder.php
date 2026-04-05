<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
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

        // Cache collections before the loop
        $brandIds = Brand::pluck('id')->toArray();
        $measurementUnitIds = MeasurementUnit::pluck('id')->toArray();
        $categoryIds = Category::pluck('id')->toArray();

        // Option templates with contextual values for configurable products
        $optionTemplates = [
            'Color' => ['Red', 'Blue', 'Green', 'Black', 'White', 'Yellow'],
            'Size' => ['S', 'M', 'L', 'XL'],
            'Material' => ['Cotton', 'Polyester', 'Leather', 'Wood', 'Metal'],
            'Capacity' => ['250ml', '500ml', '1L', '2L'],
            'Style' => ['Classic', 'Modern', 'Vintage', 'Minimalist'],
        ];

        $optionNames = array_keys($optionTemplates);

        // Create 50 products
        for ($i = 0; $i < 50; $i++) {
            $product = Product::factory()->create([
                'brand_id' => $brandIds[array_rand($brandIds)],
                'measurement_unit_id' => $measurementUnitIds[array_rand($measurementUnitIds)],
            ]);

            // Attach 1-2 categories
            $product->categories()->attach(
                $categoryIds[array_rand($categoryIds)]
            );

            // ~10% chance of inactive status
            if (random_int(1, 10) === 1) {
                $product->update(['status' => 'inactive']);
            }

            // Every product gets a default variant
            $defaultVariant = $product->variants()->create([
                'identifier' => 'SKU-'
                    . mb_strtoupper(mb_substr($product->name, 0, 3))
                    . '-'
                    . random_int(1000, 9999),
                'barcode' => 'BC-' . random_int(100000, 999999),
                'price' => fake()->randomFloat(2, 10, 1000),
                'stock' => random_int(10, 100),
                'status' => 'active',
            ]);

            // ~40% configurable (options + multi-variant), ~60% simple (default variant only)
            $isConfigurable = random_int(1, 100) <= 40;

            if (! $isConfigurable) {
                continue;
            }

            // Pick 1-2 options (70% chance of 1 option, 30% chance of 2)
            $numOptions = random_int(1, 100) <= 70 ? 1 : 2;
            shuffle($optionNames);
            $selectedOptionNames = array_slice($optionNames, 0, $numOptions);

            $optionValueIds = [];
            $optionValueNames = [];

            foreach ($selectedOptionNames as $optionName) {
                $option = $product->options()->create([
                    'name' => $optionName,
                ]);

                $availableValues = $optionTemplates[$optionName];
                shuffle($availableValues);
                $numValues = random_int(2, min(4, count($availableValues)));
                $selectedValues = array_slice($availableValues, 0, $numValues);

                $optionValueIds[$option->id] = [];
                $optionValueNames[$option->id] = [];

                foreach ($selectedValues as $val) {
                    $optionValue = ProductOptionValue::factory()
                        ->withValue($val)
                        ->create(['product_option_id' => $option->id]);

                    $optionValueIds[$option->id][] = $optionValue->id;
                    $optionValueNames[$option->id][] = $val;
                }
            }

            // Compute Cartesian product of option values
            $cartesian = [[]];
            foreach ($optionValueIds as $optionId => $valueIds) {
                $newCartesian = [];
                foreach ($cartesian as $combo) {
                    foreach ($valueIds as $valueId) {
                        $newCartesian[] = $combo + [$optionId => $valueId];
                    }
                }
                $cartesian = $newCartesian;
            }

            // First combination updates the default variant, rest create new variants
            $isFirst = true;
            foreach ($cartesian as $combo) {
                $suffixParts = [];
                foreach ($combo as $optId => $valId) {
                    $idx = array_search($valId, $optionValueIds[$optId]);
                    $suffixParts[] = mb_strtoupper(
                        mb_substr($optionValueNames[$optId][$idx], 0, 3)
                    );
                }
                $suffix = implode('-', $suffixParts);

                if ($isFirst) {
                    $defaultVariant->update([
                        'identifier' => 'SKU-'
                            . mb_strtoupper(mb_substr($product->name, 0, 3))
                            . '-' . $suffix
                            . '-' . random_int(1000, 9999),
                    ]);
                    $defaultVariant->values()->attach(array_values($combo));
                    $isFirst = false;

                    continue;
                }

                $variant = $product->variants()->create([
                    'identifier' => 'SKU-'
                        . mb_strtoupper(mb_substr($product->name, 0, 3))
                        . '-' . $suffix
                        . '-' . random_int(1000, 9999),
                    'barcode' => 'BC-' . random_int(100000, 999999),
                    'price' => fake()->randomFloat(2, 10, 1000),
                    'stock' => random_int(10, 100),
                    'status' => 'active',
                ]);

                $variant->values()->attach(array_values($combo));
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
