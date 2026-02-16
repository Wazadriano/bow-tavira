<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update default admin: login "admin", password "admin123"
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@tavira-bow.local',
                'password' => Hash::make('admin123'),
                'full_name' => 'Administrator',
                'role' => 'admin',
                'is_active' => true,
                'primary_department' => null,
            ]
        );

        $this->command->info('Default admin: username=admin, password=admin123');
    }
}
