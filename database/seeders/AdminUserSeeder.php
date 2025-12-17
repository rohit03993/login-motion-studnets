<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin if doesn't exist
        User::firstOrCreate(
            ['email' => '
            '],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
            ]
        );

        $this->command->info('Super admin created:');
        $this->command->info('Email: admin@attendance.local');
        $this->command->info('Password: admin123');
        $this->command->warn('Please change the password after first login!');
    }
}

