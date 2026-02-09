<?php

namespace Database\Seeders;

use App\Models\SettingList;
use Illuminate\Database\Seeder;

class SettingListSeeder extends Seeder
{
    public function run(): void
    {
        // Departments (from existing data)
        $departments = [
            'Corporate Governance',
            'Finance',
            'Technology',
            'Operations',
            'Compliance',
            'Risk Management',
            'Human Resources',
            'Legal',
        ];

        foreach ($departments as $order => $dept) {
            SettingList::firstOrCreate(
                ['type' => 'department', 'value' => $dept],
                ['label' => $dept, 'order' => $order, 'is_active' => true]
            );
        }

        // Activity Types (from existing data)
        $activities = [
            'Revenue Growth',
            'People',
            'Infrastructure',
            'Corporate Governance',
            'Risk Management',
            'Compliance',
            'Operations',
            'Technology',
        ];

        foreach ($activities as $order => $activity) {
            SettingList::firstOrCreate(
                ['type' => 'activity', 'value' => $activity],
                ['label' => $activity, 'order' => $order, 'is_active' => true]
            );
        }

        // Entities
        $entities = [
            ['value' => 'CH', 'label' => 'Switzerland'],
            ['value' => 'UK', 'label' => 'United Kingdom'],
            ['value' => 'Dubai', 'label' => 'Dubai'],
            ['value' => 'Monaco', 'label' => 'Monaco'],
            ['value' => 'Singapore', 'label' => 'Singapore'],
            ['value' => 'Australia', 'label' => 'Australia'],
            ['value' => 'Global', 'label' => 'Global'],
        ];

        foreach ($entities as $order => $entity) {
            SettingList::firstOrCreate(
                ['type' => 'entity', 'value' => $entity['value']],
                ['label' => $entity['label'], 'order' => $order, 'is_active' => true]
            );
        }

        // Vendor Categories
        $vendorCategories = [
            'Technology',
            'Consulting',
            'Legal',
            'Financial Services',
            'Office Services',
            'Marketing',
            'Human Resources',
            'Data Services',
        ];

        foreach ($vendorCategories as $order => $category) {
            SettingList::firstOrCreate(
                ['type' => 'vendor_category', 'value' => $category],
                ['label' => $category, 'order' => $order, 'is_active' => true]
            );
        }

        $this->command->info('Setting lists seeded successfully');
    }
}
