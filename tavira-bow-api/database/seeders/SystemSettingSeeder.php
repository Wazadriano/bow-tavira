<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'app_name',
                'value' => 'Tavira BOW',
                'type' => 'string',
                'description' => 'Application name displayed in UI',
            ],
            [
                'key' => 'task_reminder_days',
                'value' => '7',
                'type' => 'integer',
                'description' => 'Days before deadline to send task reminders',
            ],
            [
                'key' => 'contract_alert_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Days before contract expiry to send alerts',
            ],
            [
                'key' => 'risk_appetite_default',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Default risk appetite level (1-5)',
            ],
            [
                'key' => 'enable_email_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable email notifications system-wide',
            ],
            [
                'key' => 'dashboard_cache_ttl',
                'value' => '300',
                'type' => 'integer',
                'description' => 'Dashboard cache TTL in seconds',
            ],
            [
                'key' => 'max_upload_size_mb',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Maximum file upload size in megabytes',
            ],
            [
                'key' => 'allowed_file_types',
                'value' => '["pdf","doc","docx","xls","xlsx","csv","png","jpg","jpeg"]',
                'type' => 'json',
                'description' => 'Allowed file types for uploads',
            ],
            [
                'key' => 'default_currency',
                'value' => 'GBP',
                'type' => 'string',
                'description' => 'Default currency for financial values',
            ],
            [
                'key' => 'date_format',
                'value' => 'd/m/Y',
                'type' => 'string',
                'description' => 'Date format for display',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('System settings seeded successfully');
    }
}
