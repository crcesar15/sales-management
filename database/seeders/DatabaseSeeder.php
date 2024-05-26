<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MeasureUnit;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles Admin
        Role::factory(1)->create([
            'name' => 'Administrator',
        ]);

        //Roles Salesman
        Role::factory(1)->create([
            'name' => 'Salesman',
        ]);

        // Admin user
        User::factory(1)->create([
            'first_name' => 'Cesar',
            'last_name' => 'Rodriguez',
            'email' => 'crcesar15@gmail.com',
            'username' => 'admin',
            'phone' => '123456789',
            'status' => 'ACTIVE',
            'date_of_birth' => '1990-01-01',
            'role_id' => 1,
            'password' => bcrypt('123456'),
        ]);

        // Create 10 Salesman users
        User::factory(10)->create([
            'role_id' => 2,
        ]);

        //Create 10 Brands
        Brand::factory(10)->create();

        //Create 10 Measure Units
        MeasureUnit::factory(10)->create();

        //Delete and create products folder
        Storage::deleteDirectory('public/products');
        Storage::makeDirectory('public/products');

        //Product categories
        Category::factory(10)->create()->each(function ($category) {
            //create 5 products for each category
            Product::factory(5)->create([
                'category_id' => $category->id,
            ])->each(function ($product) {
                // create 1 variant for each product
                ProductVariant::factory(1)->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                ])->each(function ($variant) {
                    //create between 0 to 2 media for each variant
                    Media::factory(rand(0, 2))->create([
                        'model_id' => $variant->id,
                        'model_type' => Product::class,
                    ]);
                });
            });
        });

        //Create 10 suppliers
        Supplier::factory(10)->create();

        //Create 10 catalogs
        Catalog::factory(10)->create(
            [
                'supplier_id' => Supplier::all()->random()->id,
                'product_id' => Product::all()->random()->id,
            ]
        );

        //Create 10 purchase orders
        PurchaseOrder::factory(10)->create(
            [
                'user_id' => User::all()->random()->id,
                'supplier_id' => Supplier::all()->random()->id,
            ]
        );

        //Set permissions to storage folder
        exec('sudo chmod -R 777 storage/app/public/products');
    }
}
