<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Income permissions
            'view income',
            'create income',
            'update income',
            'delete income',

            // Expense permissions
            'view expenses',
            'create expense',
            'update expense',
            'delete expense',

            // Category permissions
            'view categories',
            'create category',
            'update category',
            'delete category',

            // Export permissions
            'export data',
            'view export history',

            // Dashboard permissions
            'view dashboard',
            'view statistics',

            // Settings permissions
            'update settings',
            'view settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions

        // Admin role - has all permissions
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->givePermissionTo(Permission::all());

        // User role - basic user with standard permissions
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'sanctum']);
        $userRole->givePermissionTo([
            'view income',
            'create income',
            'update income',
            'delete income',
            'view expenses',
            'create expense',
            'update expense',
            'delete expense',
            'view categories',
            'create category',
            'update category',
            'delete category',
            'export data',
            'view export history',
            'view dashboard',
            'view statistics',
            'update settings',
            'view settings',
        ]);

        // Guest role - read-only access
        $guestRole = Role::create(['name' => 'guest', 'guard_name' => 'sanctum']);
        $guestRole->givePermissionTo([
            'view expenses',
            'view categories',
            'view dashboard',
            'view settings',
        ]);
    }
}
