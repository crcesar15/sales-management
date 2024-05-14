<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
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

        //Delete and create products folder
        Storage::deleteDirectory('public/products');
        Storage::makeDirectory('public/products');

        //Product categories
        Category::factory(10)->create()->each(function ($category) {
            //create 5 products for each category
            Product::factory(5)->create([
                'category_id' => $category->id,
            ])->each(function ($product) {
                //create between 0 to 2 media for each product
                Media::factory(rand(0, 2))->create([
                    'model_id' => $product->id,
                    'model_type' => Product::class,
                ]);
            });
        });

        //Set permissions to storage folder
        exec('sudo chmod -R 777 storage/app/public/products');
    }
}
