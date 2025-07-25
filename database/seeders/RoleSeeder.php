<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

final class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = RolesEnum::cases();

        foreach ($roles as $role) {
            Role::create([
                'name' => $role->value,
            ]);
        }
    }
}
