<?php

namespace Database\Seeders;

use App\Enums\CurrentStatus;
use App\Enums\GovernanceFrequency;
use App\Enums\GovernanceLocation;
use App\Enums\RAGStatus;
use App\Models\GovernanceItem;
use App\Models\GovernanceItemAccess;
use App\Models\GovernanceMilestone;
use App\Models\User;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelGovernanceSeeder extends Seeder
{
    /** @var array<string, int> */
    private array $userLookup = [];

    private int $govCounter = 0;

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

        // Sheet name has a typo in the Excel: "Goverance" instead of "Governance"
        $sheet = $spreadsheet->getSheetByName('Goverance List (Lisa)');
        if (! $sheet) {
            $this->command->error('Sheet "Goverance List (Lisa)" not found');

            return;
        }

        $highestRow = $sheet->getHighestRow();
        $currentCategory = 'General';
        $milestoneCount = 0;

        // Data starts at row 8
        for ($row = 8; $row <= $highestRow; $row++) {
            $activity = trim((string) $sheet->getCell('A'.$row)->getValue());
            $owner = trim((string) $sheet->getCell('F'.$row)->getValue());
            $comments = trim((string) $sheet->getCell('G'.$row)->getValue());
            $secondaryPerson = trim((string) $sheet->getCell('H'.$row)->getValue());
            $bowRef = $sheet->getCell('I'.$row)->getValue() ?? $sheet->getCell('J'.$row)->getValue();

            if (! $activity) {
                continue;
            }

            // Category headers have content in A but no owner
            // and are typically short generic labels
            if (! $owner && ! $comments && strlen($activity) < 40) {
                $currentCategory = $activity;

                continue;
            }

            // Multi-line descriptions without owner are sub-descriptions, skip
            if (! $owner && ! $comments) {
                continue;
            }

            $this->govCounter++;
            $refNo = sprintf('GOV-%03d', $this->govCounter);

            // Clean activity text (remove markdown-like formatting)
            $activity = str_replace(['*', '§'], '', $activity);
            $activity = preg_replace('/\s+/', ' ', $activity);
            $activity = trim($activity);
            if (strlen($activity) > 255) {
                $activity = mb_substr($activity, 0, 252).'...';
            }

            $ownerId = $this->resolveOwner($owner);
            $status = $this->determineStatus($comments, $sheet->getCell('D'.$row)->getValue());
            $ragStatus = $this->determineRAG($status, $comments, $sheet->getCell('D'.$row)->getValue());
            $frequency = $this->guessFrequency($activity);
            $deadline = $this->guessDeadline($sheet->getCell('D'.$row)->getValue(), $sheet->getCell('E'.$row)->getValue());

            $item = GovernanceItem::updateOrCreate(
                ['ref_no' => $refNo],
                [
                    'activity' => $activity,
                    'description' => $comments ?: $activity,
                    'frequency' => $frequency,
                    'location' => GovernanceLocation::UK,
                    'department' => $this->categoryToDept($currentCategory),
                    'responsible_party_id' => $ownerId,
                    'current_status' => $status,
                    'rag_status' => $ragStatus,
                    'deadline' => $deadline,
                    'monthly_update' => $comments ?: null,
                ]
            );

            // Create milestones from comments if they reference steps
            if ($comments) {
                $milestoneCount += $this->createMilestones($item, $comments, $deadline);
            }

            // Give admin access
            $this->grantAdminAccess($item);

            // Give owner access
            if ($ownerId) {
                GovernanceItemAccess::firstOrCreate(
                    ['governance_item_id' => $item->id, 'user_id' => $ownerId],
                    ['can_view' => true, 'can_edit' => true]
                );
            }

            // Secondary person gets view access
            if ($secondaryPerson) {
                $secondaryId = $this->resolveOwner($secondaryPerson);
                if ($secondaryId && $secondaryId !== $ownerId) {
                    GovernanceItemAccess::firstOrCreate(
                        ['governance_item_id' => $item->id, 'user_id' => $secondaryId],
                        ['can_view' => true, 'can_edit' => false]
                    );
                }
            }
        }

        $this->command->info("Governance Items: {$this->govCounter} imported, {$milestoneCount} milestones");
    }

    private function buildUserLookup(): void
    {
        $users = User::all();
        foreach ($users as $user) {
            $key = mb_strtolower(trim($user->full_name));
            $this->userLookup[$key] = $user->id;
            // Also index by first name for short references
            $firstName = explode(' ', $key)[0];
            if (! isset($this->userLookup[$firstName])) {
                $this->userLookup[$firstName] = $user->id;
            }
        }
    }

    private function resolveOwner(?string $name): ?int
    {
        if (! $name || $name === '') {
            return null;
        }

        $name = mb_strtolower(trim($name));

        // Direct match
        if (isset($this->userLookup[$name])) {
            return $this->userLookup[$name];
        }

        // Handle composite owners like "Ranjit/Olivier" - take the first one
        if (str_contains($name, '/')) {
            $first = trim(explode('/', $name)[0]);

            return $this->resolveOwner($first);
        }

        // Handle "Mark then Elizabeth" - take the first one
        if (str_contains($name, ' then ') || str_contains($name, ' and ')) {
            $first = trim(preg_split('/ then | and /', $name)[0]);

            return $this->resolveOwner($first);
        }

        // "Board", "ManCo", "SMFs", "Chair and CEO" -> map to admin
        $orgNames = ['board', 'manco', 'smfs', 'chair and ceo', 'chair'];
        if (in_array($name, $orgNames)) {
            return User::where('role', 'admin')->first()?->id;
        }

        // First name match
        $firstName = explode(' ', $name)[0];
        if (isset($this->userLookup[$firstName])) {
            return $this->userLookup[$firstName];
        }

        return null;
    }

    private function determineStatus(?string $comments, $colD): CurrentStatus
    {
        if ($colD && strtolower(trim((string) $colD)) === 'overdue') {
            return CurrentStatus::IN_PROGRESS;
        }

        if (! $comments) {
            return CurrentStatus::NOT_STARTED;
        }

        $lower = strtolower($comments);
        if (str_contains($lower, 'completed') || str_contains($lower, 'approved') || str_contains($lower, 'in place')) {
            return CurrentStatus::COMPLETED;
        }
        if (str_contains($lower, 'progressing') || str_contains($lower, 'introduced') || str_contains($lower, 'recruited') || str_contains($lower, 'next step')) {
            return CurrentStatus::IN_PROGRESS;
        }
        if (str_contains($lower, 'scheduled') || str_contains($lower, 'discuss')) {
            return CurrentStatus::NOT_STARTED;
        }

        return CurrentStatus::IN_PROGRESS;
    }

    private function determineRAG(CurrentStatus $status, ?string $comments, $colD): RAGStatus
    {
        if ($status === CurrentStatus::COMPLETED) {
            return RAGStatus::GREEN;
        }

        if ($colD && strtolower(trim((string) $colD)) === 'overdue') {
            return RAGStatus::RED;
        }

        if ($status === CurrentStatus::IN_PROGRESS) {
            return RAGStatus::AMBER;
        }

        return RAGStatus::BLUE;
    }

    private function guessFrequency(string $activity): GovernanceFrequency
    {
        $lower = strtolower($activity);
        if (str_contains($lower, 'quarterly')) {
            return GovernanceFrequency::QUARTERLY;
        }
        if (str_contains($lower, 'monthly')) {
            return GovernanceFrequency::MONTHLY;
        }
        if (str_contains($lower, 'weekly')) {
            return GovernanceFrequency::MONTHLY; // No weekly, use monthly
        }
        if (str_contains($lower, 'annual') || str_contains($lower, 'regular review')) {
            return GovernanceFrequency::ANNUALLY;
        }

        return GovernanceFrequency::AD_HOC;
    }

    private function guessDeadline($colD, $colE): ?string
    {
        if ($colE && trim((string) $colE) !== '') {
            return '2026-12-31'; // 2026 priority
        }
        if ($colD && strtolower(trim((string) $colD)) === 'overdue') {
            return '2025-12-31'; // Was due 2H 2025
        }
        if ($colD && trim((string) $colD) !== '') {
            return '2025-12-31'; // 2H 2025 priority
        }

        return '2026-06-30'; // Default mid-year
    }

    private function categoryToDept(string $category): string
    {
        return match ($category) {
            '3LOD' => 'Risk Management',
            'Ongoing effectiveness' => 'Corporate Governance',
            'Roles and responsibilities' => 'Human Resources',
            default => 'Corporate Governance',
        };
    }

    private function createMilestones(GovernanceItem $item, string $comments, ?string $deadline): int
    {
        $steps = preg_split('/(?:\d+[\.\)]\s|Next step:|However,)/', $comments);
        if (count($steps) < 2) {
            // Create a single milestone from the comment
            GovernanceMilestone::firstOrCreate(
                ['governance_item_id' => $item->id, 'order' => 0],
                [
                    'title' => mb_substr($item->activity, 0, 255),
                    'description' => mb_substr($comments, 0, 500),
                    'target_date' => $deadline ?? '2026-06-30',
                    'status' => $item->current_status === CurrentStatus::COMPLETED ? 'Completed' : 'Not Started',
                ]
            );

            return 1;
        }

        $count = 0;
        foreach ($steps as $i => $step) {
            $step = trim($step);
            if (strlen($step) < 5) {
                continue;
            }

            GovernanceMilestone::firstOrCreate(
                ['governance_item_id' => $item->id, 'order' => $i],
                [
                    'title' => mb_substr($step, 0, 255),
                    'description' => $step,
                    'target_date' => $deadline ?? '2026-06-30',
                    'status' => $i === 0 && $item->current_status === CurrentStatus::COMPLETED ? 'Completed' : 'Not Started',
                ]
            );
            $count++;
        }

        return $count;
    }

    private function grantAdminAccess(GovernanceItem $item): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            GovernanceItemAccess::firstOrCreate(
                ['governance_item_id' => $item->id, 'user_id' => $admin->id],
                ['can_view' => true, 'can_edit' => true]
            );
        }
    }
}
