<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PDO;

class MigrateSqliteData extends Command
{
    protected $signature = 'migrate:sqlite
                            {sqlite_path : Path to the SQLite database file}
                            {--dry-run : Simulate migration without writing data}
                            {--skip-users : Skip user migration (keep existing)}';

    protected $description = 'Migrate data from SQLite (TAVIRA_BOW) to PostgreSQL';

    private PDO $sqlite;

    private array $stats = [];

    private bool $dryRun = false;

    // Table mapping: sqlite_table => [postgresql_table, transform_callback]
    private array $tableMappings = [
        // Order matters for foreign key dependencies
        'users' => 'users',
        'user_department_permission' => 'user_department_permissions',
        'work_item' => 'work_items',
        'task_dependency' => 'task_dependencies',
        'team' => 'teams',
        'team_member' => 'team_members',
        'task_assignment' => 'task_assignments',
        'task_milestone' => 'task_milestones',
        'milestone_assignment' => 'milestone_assignments',
        'governance_item' => 'governance_items',
        'governance_item_access' => 'governance_item_access',
        'governance_milestone' => 'governance_milestones',
        'governance_attachment' => 'governance_attachments',
        'sage_category' => 'sage_categories',
        'supplier' => 'suppliers',
        'supplier_entities' => 'supplier_entities',
        'supplier_access' => 'supplier_access',
        'supplier_contract' => 'supplier_contracts',
        'contract_entities' => 'contract_entities',
        'supplier_invoice' => 'supplier_invoices',
        'supplier_attachment' => 'supplier_attachments',
        'supplier_contract_attachment' => 'supplier_contract_attachments',
        'setting_list' => 'setting_lists',
        'system_setting' => 'system_settings',
        'risk_theme' => 'risk_themes',
        'risk_category' => 'risk_categories',
        'risk' => 'risks',
        'risk_work_items' => 'risk_work_items',
        'risk_governance_items' => 'risk_governance_items',
        'control_library' => 'control_library',
        'risk_control' => 'risk_controls',
        'risk_action' => 'risk_actions',
        'risk_attachment' => 'risk_attachments',
        'risk_theme_permission' => 'risk_theme_permissions',
    ];

    public function handle(): int
    {
        $sqlitePath = $this->argument('sqlite_path');
        $this->dryRun = $this->option('dry-run');

        if (! file_exists($sqlitePath)) {
            $this->error("SQLite file not found: {$sqlitePath}");

            return 1;
        }

        $this->info("Migrating data from: {$sqlitePath}");
        if ($this->dryRun) {
            $this->warn('DRY RUN MODE - No data will be written');
        }

        try {
            // Connect to SQLite
            $this->sqlite = new PDO("sqlite:{$sqlitePath}");
            $this->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Start transaction
            if (! $this->dryRun) {
                DB::beginTransaction();
            }

            // Disable foreign key checks for PostgreSQL
            if (! $this->dryRun) {
                DB::statement('SET session_replication_role = replica;');
            }

            // Migrate tables in order
            foreach ($this->tableMappings as $sqliteTable => $pgTable) {
                if ($sqliteTable === 'users' && $this->option('skip-users')) {
                    $this->info('Skipping users table');

                    continue;
                }

                $this->migrateTable($sqliteTable, $pgTable);
            }

            // Re-enable foreign key checks
            if (! $this->dryRun) {
                DB::statement('SET session_replication_role = DEFAULT;');
            }

            // Reset sequences
            if (! $this->dryRun) {
                $this->resetSequences();
            }

            // Commit transaction
            if (! $this->dryRun) {
                DB::commit();
            }

            // Display summary
            $this->displaySummary();

            $this->info('Migration completed successfully!');

            return 0;

        } catch (\Exception $e) {
            if (! $this->dryRun) {
                DB::rollBack();
            }
            $this->error('Migration failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }

    private function migrateTable(string $sqliteTable, string $pgTable): void
    {
        // Check if SQLite table exists
        $check = $this->sqlite->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='{$sqliteTable}'"
        );

        if (! $check->fetch()) {
            $this->warn("Table not found in SQLite: {$sqliteTable}");
            $this->stats[$pgTable] = ['skipped' => true];

            return;
        }

        // Check if PostgreSQL table exists
        if (! Schema::hasTable($pgTable)) {
            $this->warn("Table not found in PostgreSQL: {$pgTable}");
            $this->stats[$pgTable] = ['skipped' => true];

            return;
        }

        $this->info("Migrating: {$sqliteTable} -> {$pgTable}");

        // Fetch all data from SQLite
        $rows = $this->sqlite->query("SELECT * FROM {$sqliteTable}")->fetchAll(PDO::FETCH_ASSOC);
        $count = count($rows);

        if ($count === 0) {
            $this->line('  No data to migrate');
            $this->stats[$pgTable] = ['rows' => 0];

            return;
        }

        // Get PostgreSQL columns
        $pgColumns = Schema::getColumnListing($pgTable);

        // Transform and insert data
        $transformed = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                $data = $this->transformRow($row, $sqliteTable, $pgColumns);

                if (! $this->dryRun && $data) {
                    DB::table($pgTable)->insert($data);
                }
                $transformed++;

            } catch (\Exception $e) {
                $this->warn("  Error row ID {$row['id']}: ".$e->getMessage());
                $errors++;
            }
        }

        $this->line("  Migrated: {$transformed}/{$count} rows".($errors ? " ({$errors} errors)" : ''));
        $this->stats[$pgTable] = ['rows' => $transformed, 'errors' => $errors];
    }

    private function transformRow(array $row, string $table, array $pgColumns): array
    {
        $data = [];

        foreach ($row as $column => $value) {
            // Map column names (snake_case normalization)
            $pgColumn = $this->mapColumnName($column, $table);

            // Skip if column doesn't exist in PostgreSQL
            if (! in_array($pgColumn, $pgColumns)) {
                continue;
            }

            // Transform value
            $data[$pgColumn] = $this->transformValue($value, $pgColumn, $table);
        }

        // Special transformations by table
        $data = $this->applyTableTransformations($data, $table);

        return $data;
    }

    private function mapColumnName(string $column, string $table): string
    {
        // Handle specific mappings
        $mappings = [
            'work_item' => [
                'responsible_party' => 'responsible_party_id',
            ],
            'risk' => [
                'owner' => 'owner_id',
                'responsible_party' => 'responsible_party_id',
                'category' => 'category_id',
            ],
        ];

        if (isset($mappings[$table][$column])) {
            return $mappings[$table][$column];
        }

        return $column;
    }

    private function transformValue($value, string $column, string $table)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Boolean transformation
        if (in_array($column, ['is_active', 'priority_item', 'is_common_provider', 'is_lead', 'can_view', 'can_edit', 'can_edit_status', 'can_create_tasks', 'can_edit_all', 'can_create', 'can_delete', 'auto_renewal', 'alert_sent'])) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        // JSON transformation
        if ($column === 'tags' && is_string($value)) {
            $decoded = json_decode($value, true);

            return $decoded !== null ? json_encode($decoded) : null;
        }

        // Date transformation (ensure ISO format)
        if (str_contains($column, '_date') || str_contains($column, '_at') || $column === 'deadline') {
            if ($value && strtotime($value)) {
                return date('Y-m-d H:i:s', strtotime($value));
            }

            return null;
        }

        return $value;
    }

    private function applyTableTransformations(array $data, string $table): array
    {
        // Ensure timestamps exist
        if (! isset($data['created_at'])) {
            $data['created_at'] = now();
        }
        if (! isset($data['updated_at'])) {
            $data['updated_at'] = now();
        }

        // Table-specific transformations
        switch ($table) {
            case 'users':
                // Ensure password is hashed
                if (isset($data['password']) && strlen($data['password']) < 50) {
                    $data['password'] = Hash::make($data['password']);
                }
                // Default role
                if (empty($data['role'])) {
                    $data['role'] = 'member';
                }
                break;

            case 'work_item':
                // Ensure required fields have defaults
                $data['bau_or_transformative'] = $data['bau_or_transformative'] ?? 'BAU';
                $data['impact_level'] = $data['impact_level'] ?? 'Medium';
                $data['current_status'] = $data['current_status'] ?? 'Not Started';
                $data['update_frequency'] = $data['update_frequency'] ?? 'Quarterly';
                break;

            case 'risk':
                // Ensure scores are within range
                foreach (['financial_impact', 'regulatory_impact', 'reputational_impact', 'inherent_probability'] as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = max(1, min(5, (int) $data[$field]));
                    }
                }
                break;
        }

        return $data;
    }

    private function resetSequences(): void
    {
        $this->info('Resetting PostgreSQL sequences...');

        foreach ($this->tableMappings as $pgTable) {
            if (! Schema::hasTable($pgTable)) {
                continue;
            }

            $max = DB::table($pgTable)->max('id') ?? 0;
            if ($max > 0) {
                $sequence = "{$pgTable}_id_seq";
                try {
                    DB::statement("SELECT setval('{$sequence}', {$max})");
                } catch (\Exception $e) {
                    // Sequence might not exist for pivot tables
                }
            }
        }
    }

    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('=== Migration Summary ===');

        $total = 0;
        $errors = 0;

        foreach ($this->stats as $table => $stat) {
            if (isset($stat['skipped']) && $stat['skipped']) {
                $this->line("  {$table}: SKIPPED");
            } else {
                $rows = $stat['rows'] ?? 0;
                $errs = $stat['errors'] ?? 0;
                $total += $rows;
                $errors += $errs;
                $this->line("  {$table}: {$rows} rows".($errs ? " ({$errs} errors)" : ''));
            }
        }

        $this->newLine();
        $this->info("Total: {$total} rows migrated, {$errors} errors");
    }
}
