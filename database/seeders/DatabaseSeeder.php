<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(1)->create([
            'name' => 'Cesar Rodriguez',
            'email' => 'crcesar15@gmail.com',
            'password' => bcrypt('sample123'),
        ]);
        $this->call([ProductsSeeder::class]);
    }
}
