<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\MeasureUnit;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
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
                'password' => bcrypt('123456'),
            ]
        );

        $admin->assignRole(['Super Administrator']);

        //Run settings and permissions seeder
        $this->call(SettingsSeeder::class);
        $this->call(PermissionSeeder::class);

        // Create 10 Salesman users
        User::factory(10)->create()->each(function ($user) {
            $user->assignRole(['Salesman']);
        });

        //Create 10 Brands
        Brand::factory(10)->create();

        //Create 10 Measure Units
        MeasureUnit::factory(10)->create();

        //Delete and create products folder
        if (is_dir('public/products')) {
            Storage::deleteDirectory('public/products');
        }

        Storage::makeDirectory('public/products');

        Category::factory(10)->create()->each(function ($category) {
            //create 5 products for each category
            Product::factory(5)->create([
                'measure_unit_id' => MeasureUnit::all()->random()->id,
                'brand_id' => Brand::all()->random()->id,
            ])->each(function ($product) use ($category) {
                ProductVariant::factory(rand(1, 3))->create([
                    'product_id' => $product->id,
                    'media' => json_encode([]),
                ]);
                // Add category to product
                $product->categories()->attach($category->id);
            });
        });

        //Create 10 vendors
        Vendor::factory(10)->create();

        //Create 10 catalogs
        Catalog::factory(10)->create(
            [
                'vendor_id' => Vendor::all()->random()->id,
                'product_variant_id' => Product::all()->random()->id,
            ]
        );

        //Create 10 purchase orders
        PurchaseOrder::factory(10)->create(
            [
                'user_id' => User::all()->random()->id,
                'vendor_id' => Vendor::all()->random()->id,
            ]
        )->each(function (PurchaseOrder $purchaseOrder) {
            $purchaseOrder->products()->attach(Product::all()->random()->id, [
                'quantity' => rand(1, 10),
                'price' => rand(100, 1000),
                'total' => rand(100, 1000),
            ]);
        });

        //Set permissions to storage folder
        exec('sudo chmod -R 777 storage/app/public/products');
    }
}
