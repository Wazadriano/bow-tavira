<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@tavira-bow.local',
                'password' => Hash::make('admin123'),
                'full_name' => 'Administrateur',
                'role' => 'admin',
                'is_active' => true,
                'primary_department' => null,
            ]
        );

        $this->command->info('Default admin user created: admin / admin123');
    }
}
