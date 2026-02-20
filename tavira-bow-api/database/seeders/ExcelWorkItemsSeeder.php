<?php

namespace Database\Seeders;

use App\Enums\AssignmentType;
use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use App\Models\TaskAssignment;
use App\Models\TaskMilestone;
use App\Models\User;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExcelWorkItemsSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $userLookup = [];

    public function run(): void
    {
        $filePath = database_path('seeders/data/Transformation Work List Jan 2026.xlsx');

        if (! file_exists($filePath)) {
            $this->command->error('Excel file not found: '.$filePath);

            return;
        }

        $this->buildUserLookup();

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $this->importBowList($spreadsheet);
        $this->importAdditionalItems($spreadsheet);

        $this->command->info('Work Items: '.WorkItem::count().' total in database');
    }

    private function buildUserLookup(): void
    {
        $users = User::all();
        foreach ($users as $user) {
            $key = mb_strtolower(trim($user->full_name));
            $this->userLookup[$key] = $user->id;
        }
    }

    private function lookupUserId(?string $name): ?int
    {
        if (! $name || trim($name) === '') {
            return null;
        }

        $name = mb_strtolower(trim($name));

        if (isset($this->userLookup[$name])) {
            return $this->userLookup[$name];
        }

        // Fuzzy match: try partial matching for names like "Rebecca Refell" vs "Rebecca Reffell"
        foreach ($this->userLookup as $key => $id) {
            $nameParts = explode(' ', $name);
            $keyParts = explode(' ', $key);
            if (count($nameParts) >= 2 && count($keyParts) >= 2) {
                if ($nameParts[0] === $keyParts[0] && similar_text($nameParts[1], $keyParts[1]) >= 4) {
                    return $id;
                }
            }
        }

        return null;
    }

    private function importBowList($spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('BOW List');
        if (! $sheet) {
            $this->command->error('Sheet "BOW List" not found');

            return;
        }

        $highestRow = $sheet->getHighestRow();
        $count = 0;
        $milestoneCount = 0;
        $assignmentCount = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $number = $sheet->getCell('A'.$row)->getValue();
            if (! $number || ! is_numeric($number)) {
                continue;
            }

            $refNo = sprintf('BOW-%03d', (int) $number);
            $impactedArea = $this->cleanValue($sheet->getCell('B'.$row)->getValue());
            $activityType = $this->cleanValue($sheet->getCell('C'.$row)->getValue());
            $description = $this->cleanValue($sheet->getCell('D'.$row)->getValue());
            $bauType = $this->cleanValue($sheet->getCell('E'.$row)->getValue());
            $goal = $this->cleanValue($sheet->getCell('F'.$row)->getValue());
            $milestones = $this->cleanValue($sheet->getCell('G'.$row)->getValue());
            $comments = $this->cleanValue($sheet->getCell('H'.$row)->getValue());
            $deptHead = $this->cleanValue($sheet->getCell('I'.$row)->getValue());
            $responsible = $this->cleanValue($sheet->getCell('J'.$row)->getValue());
            $backup = $this->cleanValue($sheet->getCell('K'.$row)->getValue());
            $priority = $this->cleanValue($sheet->getCell('L'.$row)->getValue());
            $impact = $this->cleanValue($sheet->getCell('M'.$row)->getValue());
            $expectedCompletion = $this->cleanValue($sheet->getCell('N'.$row)->getValue());
            $status = $this->cleanValue($sheet->getCell('O'.$row)->getValue());
            $frequency = $this->cleanValue($sheet->getCell('P'.$row)->getValue());
            $dependencies = $this->cleanValue($sheet->getCell('Q'.$row)->getValue());
            $costSavings = $this->getNumericValue($sheet->getCell('R'.$row)->getValue());
            $costFte = $this->getNumericValue($sheet->getCell('S'.$row)->getValue());
            $expectedCost = $this->getNumericValue($sheet->getCell('T'.$row)->getValue());
            $revenuePotential = $this->getNumericValue($sheet->getCell('U'.$row)->getValue());
            $issuesRisks = $this->cleanValue($sheet->getCell('W'.$row)->getValue());
            $initialProvider = $this->cleanValue($sheet->getCell('Y'.$row)->getValue());

            $deadline = $this->parseDeadline($expectedCompletion);
            $ragStatus = $this->calculateRAG($status, $deadline);
            $responsibleId = $this->lookupUserId($responsible);
            $deptHeadId = $this->lookupUserId($deptHead);
            $backupId = $this->lookupUserId($backup);

            $workItem = WorkItem::updateOrCreate(
                ['ref_no' => $refNo],
                [
                    'type' => 'BOW',
                    'activity' => $activityType ?: 'Corporate Governance',
                    'department' => $this->mapDepartment($impactedArea),
                    'description' => $description,
                    'goal' => $goal,
                    'bau_or_transformative' => $this->mapBAU($bauType),
                    'impact_level' => $this->mapImpact($impact),
                    'current_status' => $this->mapStatus($status),
                    'rag_status' => $ragStatus,
                    'deadline' => $deadline,
                    'completion_date' => $status === 'Completed' ? ($deadline ?? now()) : null,
                    'comments' => $comments,
                    'update_frequency' => $this->mapFrequency($frequency),
                    'responsible_party_id' => $responsibleId,
                    'department_head_id' => $deptHeadId,
                    'back_up_person_id' => $backupId,
                    'priority_item' => strtolower($priority ?? '') === 'yes',
                    'cost_savings' => $costSavings,
                    'cost_efficiency_fte' => $costFte,
                    'expected_cost' => $expectedCost,
                    'revenue_potential' => $revenuePotential,
                    'other_item_completion_dependences' => $dependencies,
                    'issues_risks' => $issuesRisks,
                    'initial_item_provider_editor' => $initialProvider,
                ]
            );

            // TaskAssignments
            if ($responsibleId) {
                TaskAssignment::firstOrCreate(
                    ['work_item_id' => $workItem->id, 'user_id' => $responsibleId, 'assignment_type' => AssignmentType::OWNER]
                );
                $assignmentCount++;
            }
            if ($backupId && $backupId !== $responsibleId) {
                TaskAssignment::firstOrCreate(
                    ['work_item_id' => $workItem->id, 'user_id' => $backupId, 'assignment_type' => AssignmentType::MEMBER]
                );
                $assignmentCount++;
            }

            // TaskMilestones from "Key Milestones / Steps"
            if ($milestones) {
                $milestoneCount += $this->createMilestones($workItem, $milestones);
            }

            $count++;
        }

        $this->command->info("BOW List: {$count} work items imported, {$assignmentCount} assignments, {$milestoneCount} milestones");
    }

    private function importAdditionalItems($spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('Additional Items');
        if (! $sheet) {
            $this->command->warn('Sheet "Additional Items" not found, skipping');

            return;
        }

        $highestRow = $sheet->getHighestRow();
        $count = 0;
        $nextNumber = WorkItem::where('ref_no', 'like', 'BOW-%')->count() + 1;

        for ($row = 2; $row <= $highestRow; $row++) {
            $area = $this->cleanValue($sheet->getCell('A'.$row)->getValue());
            $activity = $this->cleanValue($sheet->getCell('B'.$row)->getValue());
            $description = $this->cleanValue($sheet->getCell('C'.$row)->getValue());
            $bau = $this->cleanValue($sheet->getCell('D'.$row)->getValue());
            $goal = $this->cleanValue($sheet->getCell('E'.$row)->getValue());
            $responsible = $this->cleanValue($sheet->getCell('F'.$row)->getValue());

            if (! $description) {
                continue;
            }

            $refNo = sprintf('BOW-%03d', $nextNumber);
            $responsibleId = $this->lookupUserId($responsible);

            WorkItem::updateOrCreate(
                ['ref_no' => $refNo],
                [
                    'type' => 'Additional',
                    'activity' => $activity ?: 'Infrastructure',
                    'department' => $this->mapDepartment($area),
                    'description' => $description,
                    'goal' => $goal,
                    'bau_or_transformative' => $this->mapBAU($bau),
                    'impact_level' => ImpactLevel::MEDIUM,
                    'current_status' => CurrentStatus::NOT_STARTED,
                    'rag_status' => RAGStatus::BLUE,
                    'update_frequency' => UpdateFrequency::QUARTERLY,
                    'responsible_party_id' => $responsibleId,
                    'priority_item' => false,
                ]
            );

            if ($responsibleId) {
                $wi = WorkItem::where('ref_no', $refNo)->first();
                TaskAssignment::firstOrCreate(
                    ['work_item_id' => $wi->id, 'user_id' => $responsibleId, 'assignment_type' => AssignmentType::OWNER]
                );
            }

            $nextNumber++;
            $count++;
        }

        $this->command->info("Additional Items: {$count} imported");
    }

    private function createMilestones(WorkItem $workItem, string $text): int
    {
        $lines = preg_split('/[\n\r]+/', $text);
        $count = 0;

        foreach ($lines as $i => $line) {
            $line = trim($line);
            // Strip bullet points or numbering
            $line = preg_replace('/^[\-\*\d\.]+\s*/', '', $line);
            $line = trim($line);

            if (strlen($line) < 3) {
                continue;
            }

            TaskMilestone::firstOrCreate(
                ['work_item_id' => $workItem->id, 'order' => $i],
                [
                    'title' => mb_substr($line, 0, 255),
                    'description' => $line,
                    'target_date' => $workItem->deadline
                        ? $workItem->deadline->copy()->subDays(max(0, (count($lines) - $i) * 30))->format('Y-m-d')
                        : now()->addDays(30 * ($i + 1))->format('Y-m-d'),
                    'status' => 'Not Started',
                ]
            );
            $count++;
        }

        return $count;
    }

    private function parseDeadline(?string $value): ?string
    {
        if (! $value || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        // "Jan 2026", "Mar 2026", "Dec 2025" etc.
        try {
            $date = Carbon::createFromFormat('M Y', $value);

            return $date->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            // Try other formats
        }

        // "January 2026"
        try {
            $date = Carbon::createFromFormat('F Y', $value);

            return $date->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            // Try numeric
        }

        // Excel serial number
        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);

                return Carbon::instance($date)->format('Y-m-d');
            } catch (\Exception $e) {
                // Fall through
            }
        }

        // "Q1 2026" style
        if (preg_match('/^Q(\d)\s*(\d{4})$/', $value, $m)) {
            $month = ((int) $m[1] - 1) * 3 + 3;

            return Carbon::create((int) $m[2], $month, 1)->endOfMonth()->format('Y-m-d');
        }

        return null;
    }

    private function calculateRAG(?string $status, ?string $deadline): RAGStatus
    {
        if ($status === 'Completed') {
            return RAGStatus::GREEN;
        }

        if (! $deadline) {
            return RAGStatus::BLUE;
        }

        $deadlineDate = Carbon::parse($deadline);
        $now = now();

        if ($deadlineDate->isPast()) {
            return RAGStatus::RED;
        }

        if ($deadlineDate->diffInDays($now) <= 30) {
            return RAGStatus::AMBER;
        }

        return RAGStatus::GREEN;
    }

    private function mapDepartment(?string $area): string
    {
        if (! $area) {
            return 'Operations';
        }

        $map = [
            'Corporate Governance' => 'Corporate Governance',
            'Compliance' => 'Compliance',
            'Finance' => 'Finance',
            'HR' => 'Human Resources',
            'Product' => 'Product',
            'Operations' => 'Operations',
            'Technology' => 'Technology',
            'Risk & Credit' => 'Risk Management',
            'Marketing' => 'Marketing',
            'Real Estate' => 'Operations',
        ];

        return $map[$area] ?? $area;
    }

    private function mapBAU(?string $value): BAUType
    {
        if (! $value) {
            return BAUType::BAU;
        }

        return strtolower(trim($value)) === 'bau' ? BAUType::BAU : BAUType::NON_BAU;
    }

    private function mapImpact(?string $value): ImpactLevel
    {
        if (! $value) {
            return ImpactLevel::MEDIUM;
        }

        return match (ucfirst(strtolower(trim($value)))) {
            'High' => ImpactLevel::HIGH,
            'Low' => ImpactLevel::LOW,
            default => ImpactLevel::MEDIUM,
        };
    }

    private function mapStatus(?string $value): CurrentStatus
    {
        if (! $value) {
            return CurrentStatus::NOT_STARTED;
        }

        return match (trim($value)) {
            'In Progress' => CurrentStatus::IN_PROGRESS,
            'Completed' => CurrentStatus::COMPLETED,
            'On Hold' => CurrentStatus::ON_HOLD,
            default => CurrentStatus::NOT_STARTED,
        };
    }

    private function mapFrequency(?string $value): UpdateFrequency
    {
        if (! $value) {
            return UpdateFrequency::QUARTERLY;
        }

        return match (trim($value)) {
            'Monthly' => UpdateFrequency::MONTHLY,
            'Quarterly' => UpdateFrequency::QUARTERLY,
            'Semi Annually' => UpdateFrequency::SEMI_ANNUALLY,
            'Annually' => UpdateFrequency::ANNUALLY,
            'One-off' => UpdateFrequency::ONE_OFF,
            'Weekly' => UpdateFrequency::WEEKLY,
            default => UpdateFrequency::QUARTERLY,
        };
    }

    private function cleanValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function getNumericValue($value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
