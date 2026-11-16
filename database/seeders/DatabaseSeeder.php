<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Enums\RolesEnum;
use App\Models\User;
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

        $admin->assignRole(RolesEnum::ADMIN->value);

        // Run settings and permissions seeder
        $this->call(SettingsSeeder::class);
        $this->call(PermissionSeeder::class);
    }
}
