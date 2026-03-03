<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the admin user.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@smartbucket.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Qweytruio123*'),
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->info('Admin user seeded: admin@smartbucket.com / admin1234');
    }
}
