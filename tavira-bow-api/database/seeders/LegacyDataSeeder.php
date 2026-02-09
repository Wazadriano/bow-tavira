<?php

namespace Database\Seeders;

use App\Models\GovernanceItem;
use App\Models\SageCategory;
use App\Models\SettingList;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LegacyDataSeeder extends Seeder
{
    /**
     * Import legacy data from TAVIRA_BOW SQLite database
     */
    public function run(): void
    {
        $dataPath = database_path('seeders/data/legacy_data.json');

        if (!file_exists($dataPath)) {
            $this->command->error("Legacy data file not found: {$dataPath}");
            return;
        }

        $data = json_decode(file_get_contents($dataPath), true);

        // Import in order to respect foreign key constraints
        $this->importSageCategories($data['sage_categories'] ?? []);
        $this->importSettingLists($data['setting_lists'] ?? []);
        $this->importUsers($data['users'] ?? []);
        $this->importWorkItems($data['work_items'] ?? []);
        $this->importSuppliers($data['suppliers'] ?? []);
        $this->importGovernanceItems($data['governance_items'] ?? []);

        $this->command->info('Legacy data import completed!');
    }

    private function importSageCategories(array $categories): void
    {
        $this->command->info('Importing Sage Categories...');
        $count = 0;

        foreach ($categories as $cat) {
            SageCategory::updateOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['full_code'] ?? null,
                    'is_active' => $cat['is_active'] ?? true,
                ]
            );
            $count++;
        }

        $this->command->info("  Imported {$count} sage categories");
    }

    private function importSettingLists(array $lists): void
    {
        $this->command->info('Importing Setting Lists...');
        $count = 0;

        // Map old type names to new enum values
        $typeMap = [
            'department' => 'department',
            'entity' => 'entity',
            'activity' => 'activity',
            'impact_level' => 'impact_level',
            'rag_status' => 'rag_status',
            'location' => 'location',
            'frequency' => 'frequency',
            'status' => 'status',
        ];

        foreach ($lists as $item) {
            $type = strtolower($item['type']);
            if (!isset($typeMap[$type])) {
                continue;
            }

            SettingList::updateOrCreate(
                ['type' => $typeMap[$type], 'value' => $item['value']],
                [
                    'label' => $item['label'] ?? $item['value'],
                    'order' => $item['display_order'] ?? 0,
                    'is_active' => $item['is_active'] ?? true,
                ]
            );
            $count++;
        }

        $this->command->info("  Imported {$count} setting lists");
    }

    private function importUsers(array $users): void
    {
        $this->command->info('Importing Users...');
        $count = 0;

        foreach ($users as $user) {
            // Skip if user already exists (by email or username)
            if (User::where('email', $user['email'])->orWhere('username', $user['username'])->exists()) {
                continue;
            }

            // Map role to lowercase
            $role = strtolower($user['role'] ?? 'member');
            if (!in_array($role, ['admin', 'member'])) {
                $role = 'member';
            }

            User::create([
                'username' => $user['username'],
                'email' => $user['email'],
                'password' => Hash::make('changeme123'), // Default password
                'full_name' => $user['full_name'],
                'role' => $role,
                'is_active' => $user['is_active'] ?? true,
                'primary_department' => $user['primary_department'],
            ]);
            $count++;
        }

        $this->command->info("  Imported {$count} users");
    }

    private function importWorkItems(array $items): void
    {
        $this->command->info('Importing Work Items...');
        $count = 0;

        foreach ($items as $item) {
            // Check if ref_no already exists
            if (WorkItem::where('ref_no', $item['ref_no'])->exists()) {
                continue;
            }

            // Validate responsible_party_id exists
            $responsibleId = $item['responsible_party_id'];
            if ($responsibleId && !User::find($responsibleId)) {
                $responsibleId = null;
            }

            // Map status to valid enum values
            $status = $this->mapStatus($item['current_status'] ?? 'Not Started');

            // Map BAU
            $bau = $item['bau_or_transformative'] ?? 'BAU';
            if (!in_array($bau, ['BAU', 'Non BAU', 'Transformative'])) {
                $bau = 'BAU';
            }

            // Map impact level
            $impact = $item['impact_level'] ?? 'Medium';
            if (!in_array($impact, ['High', 'Medium', 'Low'])) {
                $impact = 'Medium';
            }

            // Map update frequency to valid enum values
            $frequency = $this->mapUpdateFrequency($item['update_frequency'] ?? 'Quarterly');

            // Parse tags
            $tags = null;
            if (!empty($item['tags'])) {
                $tags = is_string($item['tags']) ? json_decode($item['tags'], true) : $item['tags'];
            }

            WorkItem::create([
                'ref_no' => $item['ref_no'],
                'type' => $item['type'] ?? null,
                'activity' => $item['activity'] ?? null,
                'department' => $item['department'] ?? 'General',
                'description' => $item['description'] ?? null,
                'bau_or_transformative' => $bau,
                'impact_level' => $impact,
                'current_status' => $status,
                'rag_status' => $this->calculateRag($item),
                'deadline' => $item['deadline'] ?? null,
                'monthly_update' => $item['monthly_update'] ?? null,
                'update_frequency' => $frequency,
                'responsible_party_id' => $responsibleId,
                'tags' => $tags,
                'priority_item' => $item['priority_item'] ?? false,
            ]);
            $count++;
        }

        $this->command->info("  Imported {$count} work items");
    }

    private function importSuppliers(array $suppliers): void
    {
        $this->command->info('Importing Suppliers...');
        $count = 0;

        // Valid locations from SupplierLocation enum
        $validLocations = ['London', 'Monaco', 'Dubai', 'Australia', 'Global', 'Singapore', 'France'];

        foreach ($suppliers as $supplier) {
            // Skip if supplier with same name exists
            if (Supplier::where('name', $supplier['name'])->exists()) {
                continue;
            }

            // Map location - legacy has 'Local'/'Overseas', map to 'Global'
            $location = $supplier['location'] ?? 'Global';
            if (!in_array($location, $validLocations)) {
                $location = 'Global';
            }

            // Map status
            $status = $supplier['status'] ?? 'Active';
            if (!in_array($status, ['Active', 'Exited', 'Pending'])) {
                $status = 'Active';
            }

            // Validate sage_category_id
            $sageCatId = $supplier['sage_category_id'];
            if ($sageCatId && !SageCategory::find($sageCatId)) {
                $sageCatId = null;
            }

            // Parse tags
            $tags = null;
            if (!empty($supplier['tags'])) {
                $tags = is_string($supplier['tags']) ? json_decode($supplier['tags'], true) : $supplier['tags'];
            }

            Supplier::create([
                'name' => $supplier['name'],
                'category' => $supplier['category'] ?? null,
                'location' => $location,
                'status' => $status,
                'is_common_provider' => $supplier['is_common_provider'] ?? false,
                'sage_category_id' => $sageCatId,
                'notes' => $supplier['notes'] ?? null,
                'tags' => $tags,
            ]);
            $count++;
        }

        $this->command->info("  Imported {$count} suppliers");
    }

    private function importGovernanceItems(array $items): void
    {
        $this->command->info('Importing Governance Items...');
        $count = 0;

        foreach ($items as $item) {
            // Check if ref_no already exists
            if (GovernanceItem::where('ref_no', $item['ref_no'])->exists()) {
                continue;
            }

            // Validate owner_id
            $ownerId = $item['owner_id'] ?? null;
            if ($ownerId && !User::find($ownerId)) {
                $ownerId = null;
            }

            // Map frequency
            $frequency = ucfirst(strtolower($item['frequency'] ?? 'Quarterly'));
            if (!in_array($frequency, ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Annually', 'Ad Hoc'])) {
                $frequency = 'Quarterly';
            }

            // Map location
            $location = $item['location'] ?? 'Global';
            if (!in_array($location, ['Local', 'Global', 'Remote'])) {
                $location = 'Global';
            }

            GovernanceItem::create([
                'ref_no' => $item['ref_no'],
                'activity' => $item['name'] ?? null,
                'description' => $item['description'] ?? null,
                'department' => $item['department'] ?? 'General',
                'frequency' => $frequency,
                'location' => $location,
                'current_status' => $this->mapStatus($item['status'] ?? 'Not Started'),
                'rag_status' => $item['rag_status'] ?? 'Green',
                'deadline' => $item['due_date'] ?? null,
                'responsible_party_id' => $ownerId,
                'monthly_update' => $item['notes'] ?? null,
            ]);
            $count++;
        }

        $this->command->info("  Imported {$count} governance items");
    }

    private function mapStatus(string $status): string
    {
        $map = [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $lower = strtolower($status);

        // Check direct match
        if (isset($map[$lower])) {
            return $map[$lower];
        }

        // Check if already correct format
        if (in_array($status, array_values($map))) {
            return $status;
        }

        return 'Not Started';
    }

    private function calculateRag(array $item): string
    {
        // Simple RAG calculation based on deadline and status
        $deadline = $item['deadline'] ?? null;
        $status = strtolower($item['current_status'] ?? '');

        if ($status === 'completed') {
            return 'Blue';
        }

        if (!$deadline) {
            return 'Green';
        }

        $deadlineDate = strtotime($deadline);
        $now = time();
        $daysUntil = ($deadlineDate - $now) / 86400;

        if ($daysUntil < 0) {
            return 'Red';  // Overdue
        } elseif ($daysUntil < 14) {
            return 'Amber';  // Due within 2 weeks
        }

        return 'Green';
    }

    private function mapUpdateFrequency(string $frequency): string
    {
        // Map legacy frequency values to UpdateFrequency enum backed values
        $map = [
            'annually' => 'Annually',
            'semi annually' => 'Semi Annually',
            'semi_annually' => 'Semi Annually',
            'quarterly' => 'Quarterly',
            'monthly' => 'Monthly',
            'weekly' => 'Weekly',
            'daily' => 'Weekly',  // No daily in enum, use weekly
        ];

        $lower = strtolower(trim($frequency));

        if (isset($map[$lower])) {
            return $map[$lower];
        }

        // Check if already a valid value
        $validValues = ['Annually', 'Semi Annually', 'Quarterly', 'Monthly', 'Weekly'];
        if (in_array($frequency, $validValues)) {
            return $frequency;
        }

        return 'Quarterly';  // Default
    }
}
