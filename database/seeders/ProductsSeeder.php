<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Products;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Storage::deleteDirectory('public/products');
        Storage::makeDirectory('public/products');
        //create 10 products
        Products::factory(10)->create()->each(function ($product) {
            //create 3 media for each product
            Media::factory(rand(0, 3))->create([
                'model_id' => $product->id,
                'model_type' => Products::class,
            ]);
        });

        //Set permissions to storage folder
        exec('sudo chmod -R 777 storage/app/public/products');
    }
}
