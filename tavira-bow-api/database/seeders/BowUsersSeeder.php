<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BowUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['full_name' => 'Andy Webster', 'primary_department' => 'Compliance'],
            ['full_name' => 'Simon Sherwood', 'primary_department' => 'Compliance'],
            ['full_name' => 'Will Moody', 'primary_department' => 'Operations'],
            ['full_name' => 'Rebecca Reffell', 'primary_department' => 'Finance'],
            ['full_name' => 'Andrew Sherwood', 'primary_department' => 'Compliance'],
            ['full_name' => 'Mark Sherwood', 'primary_department' => 'IT'],
            ['full_name' => 'Paul Allen', 'primary_department' => 'Operations'],
            ['full_name' => 'James Sherwood', 'primary_department' => 'Operations'],
            ['full_name' => 'Sarah Mitchell', 'primary_department' => 'Finance'],
            ['full_name' => 'David Cooper', 'primary_department' => 'IT'],
            ['full_name' => 'Lisa Thompson', 'primary_department' => 'HR'],
            ['full_name' => 'Robert Clarke', 'primary_department' => 'Operations'],
            ['full_name' => 'Emma Davis', 'primary_department' => 'Finance'],
            ['full_name' => 'Michael Brown', 'primary_department' => 'IT'],
            ['full_name' => 'Rachel Green', 'primary_department' => 'HR'],
            ['full_name' => 'Thomas White', 'primary_department' => 'Compliance'],
            ['full_name' => 'Jennifer Wilson', 'primary_department' => 'Finance'],
            ['full_name' => 'Daniel Taylor', 'primary_department' => 'Operations'],
            ['full_name' => 'Karen Harris', 'primary_department' => 'HR'],
            ['full_name' => 'Steven Martin', 'primary_department' => 'IT'],
            ['full_name' => 'Amanda Jackson', 'primary_department' => 'Compliance'],
        ];

        foreach ($users as $userData) {
            $nameParts = explode(' ', strtolower($userData['full_name']));
            $email = implode('.', $nameParts).'@tavira-bow.local';
            $username = implode('.', $nameParts);

            User::firstOrCreate(
                ['email' => $email],
                [
                    'username' => $username,
                    'full_name' => $userData['full_name'],
                    'password' => Hash::make('password123'),
                    'role' => 'member',
                    'is_active' => true,
                    'primary_department' => $userData['primary_department'],
                ]
            );
        }
    }
}
