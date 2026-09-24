<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $superAdmin = User::query()->updateOrCreate(
            ['employee_code' => 'EMP-0001'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $superAdmin->assignRole(Role::SUPER_ADMIN);
    }
}
