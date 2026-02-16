<?php

namespace Database\Seeders;

use App\Enums\AssignmentType;
use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;

class WorkItemDataEnhancerSeeder extends Seeder
{
    /** @var array<int, int> */
    private array $activeUserIds = [];

    private array $tagPool = [
        'compliance', 'regulatory', 'digital-transformation', 'cost-reduction',
        'automation', 'risk-mitigation', 'client-facing', 'internal',
        'priority-q1', 'priority-q2', 'infrastructure', 'reporting',
        'audit', 'governance', 'vendor-management', 'training',
        'process-improvement', 'data-migration', 'cybersecurity', 'ESG',
    ];

    private array $monthlyUpdateTemplates = [
        'In Progress' => [
            'On track. Key milestones achieved this month. Team working on next deliverables.',
            'Progress steady. Minor dependency on vendor timeline. Escalation not required.',
            'Good progress this period. Completed initial assessment, moving to implementation phase.',
            'Sprint deliverables on schedule. Testing phase to begin next month.',
            'Resources allocated. Development underway with weekly status updates.',
            'Phase 2 underway. Stakeholder review completed. No blockers identified.',
            'Integration testing in progress. Initial results positive. UAT planned for next month.',
            'Team capacity at 80%. Parallel workstreams progressing well.',
        ],
        'On Hold' => [
            'Paused pending vendor response. Expected to resume within 2 weeks.',
            'On hold due to budget approval cycle. Will resume once FY allocation confirmed.',
            'Awaiting regulatory guidance before proceeding. No action required at this time.',
            'Dependent on infrastructure upgrade completion. Estimated restart: next quarter.',
            'Paused for strategic review. Board decision expected at next committee meeting.',
        ],
        'Not Started' => [
            'Scheduled for next quarter. Requirements gathering in preliminary phase.',
            'Awaiting resource allocation. Project brief under review.',
            'Planning phase. Stakeholder alignment meetings scheduled for next month.',
        ],
    ];

    public function run(): void
    {
        $this->activeUserIds = User::where('is_active', true)->pluck('id')->toArray();

        if (empty($this->activeUserIds)) {
            $this->command->warn('No active users found, aborting');

            return;
        }

        $workItems = WorkItem::orderBy('id')->get();

        if ($workItems->isEmpty()) {
            $this->command->warn('No work items found, aborting');

            return;
        }

        $total = $workItems->count();
        $this->command->info("Enhancing {$total} work items...");

        $statusDistribution = $this->buildStatusDistribution($total);
        $impactDistribution = $this->buildImpactDistribution($total);
        $bauDistribution = $this->buildBAUDistribution($total, $workItems);

        foreach ($workItems as $index => $workItem) {
            $status = $statusDistribution[$index];
            $impact = $impactDistribution[$index];
            $bau = $bauDistribution[$index];
            $deadline = $this->generateDeadline($status, $index, $total);
            $rag = $this->calculateRAG($status, $deadline);
            $completionDate = $status === CurrentStatus::COMPLETED
                ? $deadline->copy()->subDays(rand(1, 14))
                : null;

            $updates = [
                'current_status' => $status,
                'impact_level' => $impact,
                'bau_or_transformative' => $bau,
                'rag_status' => $rag,
                'deadline' => $deadline->format('Y-m-d'),
            ];

            if ($completionDate) {
                $updates['completion_date'] = $completionDate->format('Y-m-d');
            }

            if ($status === CurrentStatus::IN_PROGRESS || $status === CurrentStatus::ON_HOLD) {
                $templates = $this->monthlyUpdateTemplates[$status->value];
                $updates['monthly_update'] = $templates[$index % count($templates)];
            } elseif ($status === CurrentStatus::NOT_STARTED) {
                $templates = $this->monthlyUpdateTemplates['Not Started'];
                $updates['monthly_update'] = $templates[$index % count($templates)];
            }

            if ($index % 3 < 2) {
                $numTags = ($index % 3) + 1;
                $tags = [];
                for ($t = 0; $t < $numTags; $t++) {
                    $tags[] = $this->tagPool[($index + $t * 7) % count($this->tagPool)];
                }
                $updates['tags'] = array_unique($tags);
            }

            if ($index % 5 < 2) {
                $updates['cost_savings'] = rand(5, 150) * 1000;
            }
            if ($index % 5 < 3) {
                $updates['expected_cost'] = rand(10, 500) * 1000;
            }
            if ($index % 7 < 2) {
                $updates['revenue_potential'] = rand(20, 800) * 1000;
            }

            $workItem->update($updates);

            $this->createAssignments($workItem, $index);
        }

        $this->printSummary();
    }

    /** @return array<int, CurrentStatus> */
    private function buildStatusDistribution(int $total): array
    {
        $distribution = [];
        $mapping = [
            [CurrentStatus::NOT_STARTED, 0.20],
            [CurrentStatus::IN_PROGRESS, 0.35],
            [CurrentStatus::COMPLETED, 0.30],
        ];

        $assigned = 0;
        foreach ($mapping as [$status, $ratio]) {
            $count = (int) round($total * $ratio);
            $assigned += $count;
            for ($i = 0; $i < $count; $i++) {
                $distribution[] = $status;
            }
        }

        $remaining = $total - $assigned;
        for ($i = 0; $i < $remaining; $i++) {
            $distribution[] = CurrentStatus::ON_HOLD;
        }

        shuffle($distribution);

        return $distribution;
    }

    /** @return array<int, ImpactLevel> */
    private function buildImpactDistribution(int $total): array
    {
        $distribution = [];
        $mapping = [
            [ImpactLevel::HIGH, 0.25],
            [ImpactLevel::MEDIUM, 0.50],
        ];

        $assigned = 0;
        foreach ($mapping as [$level, $ratio]) {
            $count = (int) round($total * $ratio);
            $assigned += $count;
            for ($i = 0; $i < $count; $i++) {
                $distribution[] = $level;
            }
        }

        $remaining = $total - $assigned;
        for ($i = 0; $i < $remaining; $i++) {
            $distribution[] = ImpactLevel::LOW;
        }

        shuffle($distribution);

        return $distribution;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, WorkItem>  $workItems
     * @return array<int, BAUType>
     */
    private function buildBAUDistribution(int $total, $workItems): array
    {
        $transformativeDepts = ['Technology', 'Compliance', 'Finance', 'Risk Management'];
        $distribution = [];

        foreach ($workItems as $workItem) {
            $dept = $workItem->department;
            if (in_array($dept, $transformativeDepts) && rand(1, 100) <= 65) {
                $distribution[] = BAUType::NON_BAU;
            } elseif (rand(1, 100) <= 20) {
                $distribution[] = BAUType::NON_BAU;
            } else {
                $distribution[] = BAUType::BAU;
            }
        }

        return $distribution;
    }

    private function generateDeadline(CurrentStatus $status, int $index, int $total): \Carbon\Carbon
    {
        return match ($status) {
            CurrentStatus::COMPLETED => now()->subDays(rand(7, 90)),
            CurrentStatus::IN_PROGRESS => match (true) {
                $index % 5 === 0 => now()->subDays(rand(1, 14)),
                default => now()->addDays(rand(14, 180)),
            },
            CurrentStatus::ON_HOLD => now()->addDays(rand(30, 240)),
            CurrentStatus::NOT_STARTED => now()->addDays(rand(30, 365)),
        };
    }

    private function calculateRAG(CurrentStatus $status, \Carbon\Carbon $deadline): RAGStatus
    {
        if ($status === CurrentStatus::COMPLETED) {
            return RAGStatus::BLUE;
        }

        $daysUntilDeadline = now()->diffInDays($deadline, false);

        if ($status === CurrentStatus::ON_HOLD) {
            return $daysUntilDeadline > 60 ? RAGStatus::AMBER : RAGStatus::RED;
        }

        if ($status === CurrentStatus::NOT_STARTED) {
            if ($daysUntilDeadline > 90) {
                return RAGStatus::GREEN;
            }
            if ($daysUntilDeadline > 30) {
                return RAGStatus::AMBER;
            }

            return RAGStatus::RED;
        }

        if ($daysUntilDeadline < 0) {
            return RAGStatus::RED;
        }
        if ($daysUntilDeadline < 14) {
            return RAGStatus::AMBER;
        }

        return RAGStatus::GREEN;
    }

    private function createAssignments(WorkItem $workItem, int $index): void
    {
        TaskAssignment::where('work_item_id', $workItem->id)->delete();

        $ownerId = $workItem->responsible_party_id ?? $this->activeUserIds[$index % count($this->activeUserIds)];

        TaskAssignment::create([
            'work_item_id' => $workItem->id,
            'user_id' => $ownerId,
            'assignment_type' => AssignmentType::OWNER,
        ]);

        $numMembers = $index % 3;
        $usedIds = [$ownerId];

        for ($m = 0; $m < $numMembers; $m++) {
            $memberId = $this->activeUserIds[($index + $m + 5) % count($this->activeUserIds)];
            if (in_array($memberId, $usedIds)) {
                continue;
            }
            $usedIds[] = $memberId;

            TaskAssignment::create([
                'work_item_id' => $workItem->id,
                'user_id' => $memberId,
                'assignment_type' => AssignmentType::MEMBER,
            ]);
        }
    }

    private function printSummary(): void
    {
        $this->command->info('--- Enhancement Summary ---');

        $statusCounts = WorkItem::selectRaw('current_status, count(*) as cnt')->groupBy('current_status')->pluck('cnt', 'current_status');
        foreach ($statusCounts as $status => $count) {
            $this->command->info("  Status {$status}: {$count}");
        }

        $ragCounts = WorkItem::selectRaw('rag_status, count(*) as cnt')->groupBy('rag_status')->pluck('cnt', 'rag_status');
        foreach ($ragCounts as $rag => $count) {
            $this->command->info("  RAG {$rag}: {$count}");
        }

        $bauCounts = WorkItem::selectRaw('bau_or_transformative, count(*) as cnt')->groupBy('bau_or_transformative')->pluck('cnt', 'bau_or_transformative');
        foreach ($bauCounts as $bau => $count) {
            $this->command->info("  Type {$bau}: {$count}");
        }

        $impactCounts = WorkItem::selectRaw('impact_level, count(*) as cnt')->groupBy('impact_level')->pluck('cnt', 'impact_level');
        foreach ($impactCounts as $impact => $count) {
            $this->command->info("  Impact {$impact}: {$count}");
        }

        $this->command->info('  Assignments: '.TaskAssignment::count());
        $this->command->info('  With tags: '.WorkItem::whereNotNull('tags')->count());
        $this->command->info('  With cost_savings: '.WorkItem::whereNotNull('cost_savings')->where('cost_savings', '>', 0)->count());
        $this->command->info('  With expected_cost: '.WorkItem::whereNotNull('expected_cost')->where('expected_cost', '>', 0)->count());
        $this->command->info('  With revenue_potential: '.WorkItem::whereNotNull('revenue_potential')->where('revenue_potential', '>', 0)->count());
    }
}
