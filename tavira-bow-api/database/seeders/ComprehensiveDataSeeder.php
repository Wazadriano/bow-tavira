<?php

namespace Database\Seeders;

use App\Enums\ActionPriority;
use App\Enums\ActionStatus;
use App\Enums\AppetiteStatus;
use App\Enums\ControlImplementationStatus;
use App\Enums\RAGStatus;
use App\Enums\RiskTier;
use App\Models\ControlLibrary;
use App\Models\GovernanceItem;
use App\Models\MilestoneAssignment;
use App\Models\Risk;
use App\Models\RiskAction;
use App\Models\RiskCategory;
use App\Models\RiskControl;
use App\Models\RiskGovernanceItem;
use App\Models\RiskWorkItem;
use App\Models\TaskDependency;
use App\Models\TaskMilestone;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;

class ComprehensiveDataSeeder extends Seeder
{
    /** @var array<int, int> */
    private array $userIds = [];

    /** @var array<int, int> */
    private array $workItemIds = [];

    /** @var array<int, int> */
    private array $governanceItemIds = [];

    /** @var array<int, int> */
    private array $riskIds = [];

    /** @var array<int, int> */
    private array $controlIds = [];

    /** @var array<int, int> */
    private array $milestoneIds = [];

    public function run(): void
    {
        $this->loadExistingIds();
        $this->seedTeams();
        $this->seedControlLibrary();
        $this->seedRisks();
        $this->seedRiskActions();
        $this->seedRiskControls();
        $this->seedTaskMilestones();
        $this->seedTaskDependencies();
        $this->seedMilestoneAssignments();
        $this->seedRiskWorkItems();
        $this->seedRiskGovernanceItems();

        $this->command->info('Comprehensive data seeding completed!');
    }

    private function loadExistingIds(): void
    {
        $this->userIds = User::pluck('id')->toArray();
        $this->workItemIds = WorkItem::pluck('id')->toArray();
        $this->governanceItemIds = GovernanceItem::pluck('id')->toArray();

        $this->command->info('Loaded: '.count($this->userIds).' users, '.count($this->workItemIds).' work items, '.count($this->governanceItemIds).' governance items');
    }

    private function seedTeams(): void
    {
        $teams = [
            ['name' => 'Executive Committee', 'description' => 'Senior leadership and strategic oversight', 'is_active' => true],
            ['name' => 'Risk & Compliance', 'description' => 'Risk management and regulatory compliance team', 'is_active' => true],
            ['name' => 'Technology & Innovation', 'description' => 'IT infrastructure and digital transformation', 'is_active' => true],
            ['name' => 'Client Services', 'description' => 'Wealth management client relationship team', 'is_active' => true],
            ['name' => 'Operations & Finance', 'description' => 'Back-office operations and financial control', 'is_active' => true],
        ];

        foreach ($teams as $index => $teamData) {
            $team = Team::firstOrCreate(
                ['name' => $teamData['name']],
                $teamData
            );

            $memberCount = min(count($this->userIds), 5);
            $offset = $index * 2;
            for ($i = 0; $i < $memberCount; $i++) {
                $userId = $this->userIds[($offset + $i) % count($this->userIds)];
                TeamMember::firstOrCreate(
                    ['team_id' => $team->id, 'user_id' => $userId],
                    ['is_lead' => $i === 0]
                );
            }
        }

        $this->command->info('Teams: '.Team::count().' created with '.TeamMember::count().' members');
    }

    private function seedControlLibrary(): void
    {
        $controls = [
            ['code' => 'CTRL-001', 'name' => 'Access Control Review', 'description' => 'Periodic review of user access rights and privileges', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-002', 'name' => 'Transaction Monitoring', 'description' => 'Automated monitoring of transactions for suspicious activity', 'control_type' => 'Detective'],
            ['code' => 'CTRL-003', 'name' => 'Incident Response Plan', 'description' => 'Documented procedures for responding to security incidents', 'control_type' => 'Corrective'],
            ['code' => 'CTRL-004', 'name' => 'Data Encryption', 'description' => 'Encryption of sensitive data at rest and in transit', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-005', 'name' => 'KYC/AML Screening', 'description' => 'Know Your Customer and Anti-Money Laundering screening', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-006', 'name' => 'Audit Trail Review', 'description' => 'Regular review of system audit logs for anomalies', 'control_type' => 'Detective'],
            ['code' => 'CTRL-007', 'name' => 'Business Continuity Plan', 'description' => 'Procedures ensuring business operations during disruptions', 'control_type' => 'Corrective'],
            ['code' => 'CTRL-008', 'name' => 'Segregation of Duties', 'description' => 'Separation of conflicting duties to prevent fraud', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-009', 'name' => 'Vulnerability Scanning', 'description' => 'Automated scanning of systems for known vulnerabilities', 'control_type' => 'Detective'],
            ['code' => 'CTRL-010', 'name' => 'Patch Management', 'description' => 'Timely application of security patches and updates', 'control_type' => 'Corrective'],
            ['code' => 'CTRL-011', 'name' => 'Client Suitability Assessment', 'description' => 'Assessment of investment suitability for each client', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-012', 'name' => 'Reconciliation Process', 'description' => 'Daily reconciliation of positions and cash balances', 'control_type' => 'Detective'],
            ['code' => 'CTRL-013', 'name' => 'Regulatory Reporting Review', 'description' => 'Pre-submission review of regulatory reports', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-014', 'name' => 'Whistleblower Channel', 'description' => 'Anonymous channel for reporting compliance concerns', 'control_type' => 'Detective'],
            ['code' => 'CTRL-015', 'name' => 'Third Party Due Diligence', 'description' => 'Due diligence process for onboarding third parties', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-016', 'name' => 'Disaster Recovery Testing', 'description' => 'Regular testing of disaster recovery procedures', 'control_type' => 'Corrective'],
            ['code' => 'CTRL-017', 'name' => 'Conflict of Interest Register', 'description' => 'Registry and management of conflicts of interest', 'control_type' => 'Preventive'],
            ['code' => 'CTRL-018', 'name' => 'Performance Attribution Review', 'description' => 'Review of portfolio performance attribution analysis', 'control_type' => 'Detective'],
        ];

        foreach ($controls as $ctrlData) {
            $control = ControlLibrary::firstOrCreate(
                ['code' => $ctrlData['code']],
                array_merge($ctrlData, ['is_active' => true])
            );
            $this->controlIds[] = $control->id;
        }

        $this->command->info('Controls: '.count($this->controlIds).' created');
    }

    private function seedRisks(): void
    {
        $categories = RiskCategory::all();
        if ($categories->isEmpty()) {
            $this->command->warn('No risk categories found, skipping risks');

            return;
        }

        $risks = [
            ['ref_no' => 'RSK-001', 'name' => 'Regulatory Sanctions', 'description' => 'Risk of regulatory sanctions due to non-compliance with FCA requirements', 'tier' => RiskTier::TIER_A, 'financial_impact' => 5, 'regulatory_impact' => 5, 'reputational_impact' => 4, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-002', 'name' => 'Cyber Attack', 'description' => 'Risk of data breach or ransomware attack on critical systems', 'tier' => RiskTier::TIER_A, 'financial_impact' => 4, 'regulatory_impact' => 4, 'reputational_impact' => 5, 'inherent_probability' => 4, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-003', 'name' => 'Key Person Dependency', 'description' => 'Risk of losing critical knowledge if key staff leave', 'tier' => RiskTier::TIER_B, 'financial_impact' => 3, 'regulatory_impact' => 2, 'reputational_impact' => 3, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-004', 'name' => 'Client Data Loss', 'description' => 'Risk of accidental loss or corruption of client data', 'tier' => RiskTier::TIER_A, 'financial_impact' => 4, 'regulatory_impact' => 5, 'reputational_impact' => 5, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-005', 'name' => 'Market Volatility Impact', 'description' => 'Risk of significant market movements affecting AUM', 'tier' => RiskTier::TIER_B, 'financial_impact' => 4, 'regulatory_impact' => 1, 'reputational_impact' => 3, 'inherent_probability' => 4, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-006', 'name' => 'Third Party Failure', 'description' => 'Risk of critical third-party service provider failure', 'tier' => RiskTier::TIER_B, 'financial_impact' => 3, 'regulatory_impact' => 3, 'reputational_impact' => 3, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-007', 'name' => 'AML Compliance Gap', 'description' => 'Risk of gaps in anti-money laundering controls', 'tier' => RiskTier::TIER_A, 'financial_impact' => 5, 'regulatory_impact' => 5, 'reputational_impact' => 5, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-008', 'name' => 'Operational Process Failure', 'description' => 'Risk of critical operational process breakdown', 'tier' => RiskTier::TIER_B, 'financial_impact' => 3, 'regulatory_impact' => 2, 'reputational_impact' => 2, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-009', 'name' => 'Cross-Border Compliance', 'description' => 'Risk of non-compliance with multi-jurisdictional regulations', 'tier' => RiskTier::TIER_A, 'financial_impact' => 4, 'regulatory_impact' => 5, 'reputational_impact' => 4, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-010', 'name' => 'Business Continuity', 'description' => 'Risk of extended business disruption from natural disasters', 'tier' => RiskTier::TIER_B, 'financial_impact' => 4, 'regulatory_impact' => 2, 'reputational_impact' => 3, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-011', 'name' => 'Client Suitability', 'description' => 'Risk of unsuitable investment advice or product allocation', 'tier' => RiskTier::TIER_A, 'financial_impact' => 4, 'regulatory_impact' => 5, 'reputational_impact' => 4, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-012', 'name' => 'Liquidity Shortfall', 'description' => 'Risk of insufficient liquidity to meet obligations', 'tier' => RiskTier::TIER_B, 'financial_impact' => 5, 'regulatory_impact' => 3, 'reputational_impact' => 3, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-013', 'name' => 'Fraud Internal', 'description' => 'Risk of internal fraud or employee misconduct', 'tier' => RiskTier::TIER_B, 'financial_impact' => 4, 'regulatory_impact' => 4, 'reputational_impact' => 4, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-014', 'name' => 'Technology Obsolescence', 'description' => 'Risk of outdated technology creating vulnerabilities', 'tier' => RiskTier::TIER_C, 'financial_impact' => 2, 'regulatory_impact' => 1, 'reputational_impact' => 2, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-015', 'name' => 'Reputational Damage', 'description' => 'Risk of negative media coverage or client complaints', 'tier' => RiskTier::TIER_B, 'financial_impact' => 3, 'regulatory_impact' => 2, 'reputational_impact' => 5, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-016', 'name' => 'Capital Adequacy', 'description' => 'Risk of falling below minimum capital requirements', 'tier' => RiskTier::TIER_A, 'financial_impact' => 5, 'regulatory_impact' => 5, 'reputational_impact' => 4, 'inherent_probability' => 1, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-017', 'name' => 'Data Privacy Breach', 'description' => 'Risk of GDPR or data privacy regulation breach', 'tier' => RiskTier::TIER_B, 'financial_impact' => 3, 'regulatory_impact' => 4, 'reputational_impact' => 4, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-018', 'name' => 'Concentration Risk', 'description' => 'Risk from excessive concentration in single clients or markets', 'tier' => RiskTier::TIER_C, 'financial_impact' => 3, 'regulatory_impact' => 2, 'reputational_impact' => 2, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-019', 'name' => 'Sanctions Screening Failure', 'description' => 'Risk of failure to screen against sanctions lists', 'tier' => RiskTier::TIER_A, 'financial_impact' => 5, 'regulatory_impact' => 5, 'reputational_impact' => 5, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OUTSIDE],
            ['ref_no' => 'RSK-020', 'name' => 'Outsourcing Dependency', 'description' => 'Risk from over-reliance on outsourced service providers', 'tier' => RiskTier::TIER_C, 'financial_impact' => 2, 'regulatory_impact' => 2, 'reputational_impact' => 2, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-021', 'name' => 'Interest Rate Exposure', 'description' => 'Risk from sudden interest rate changes affecting portfolios', 'tier' => RiskTier::TIER_C, 'financial_impact' => 3, 'regulatory_impact' => 1, 'reputational_impact' => 2, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-022', 'name' => 'Staff Misconduct', 'description' => 'Risk of compliance breaches through staff behaviour', 'tier' => RiskTier::TIER_B, 'financial_impact' => 3, 'regulatory_impact' => 4, 'reputational_impact' => 4, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-023', 'name' => 'Model Risk', 'description' => 'Risk from errors in valuation or risk models', 'tier' => RiskTier::TIER_C, 'financial_impact' => 3, 'regulatory_impact' => 2, 'reputational_impact' => 2, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-024', 'name' => 'Geopolitical Disruption', 'description' => 'Risk from geopolitical events affecting multi-jurisdiction operations', 'tier' => RiskTier::TIER_B, 'financial_impact' => 3, 'regulatory_impact' => 2, 'reputational_impact' => 3, 'inherent_probability' => 3, 'appetite_status' => AppetiteStatus::OK],
            ['ref_no' => 'RSK-025', 'name' => 'Tax Compliance', 'description' => 'Risk of cross-border tax compliance failures', 'tier' => RiskTier::TIER_B, 'financial_impact' => 4, 'regulatory_impact' => 4, 'reputational_impact' => 3, 'inherent_probability' => 2, 'appetite_status' => AppetiteStatus::OUTSIDE],
        ];

        foreach ($risks as $riskData) {
            $category = $categories->random();
            $ownerId = $this->userIds[array_rand($this->userIds)];
            $responsibleId = $this->userIds[array_rand($this->userIds)];
            $score = $riskData['financial_impact'] * $riskData['inherent_probability'];
            $residualScore = max(1, $score * 0.6);

            $inherentRag = match (true) {
                $score >= 12 => RAGStatus::RED,
                $score >= 8 => RAGStatus::AMBER,
                $score >= 4 => RAGStatus::GREEN,
                default => RAGStatus::BLUE,
            };
            $residualRag = match (true) {
                $residualScore >= 12 => RAGStatus::RED,
                $residualScore >= 8 => RAGStatus::AMBER,
                $residualScore >= 4 => RAGStatus::GREEN,
                default => RAGStatus::BLUE,
            };

            $risk = Risk::firstOrCreate(
                ['ref_no' => $riskData['ref_no']],
                array_merge($riskData, [
                    'category_id' => $category->id,
                    'owner_id' => $ownerId,
                    'responsible_party_id' => $responsibleId,
                    'inherent_risk_score' => $score,
                    'inherent_rag' => $inherentRag,
                    'residual_risk_score' => round($residualScore, 2),
                    'residual_rag' => $residualRag,
                    'monthly_update' => 'Risk reviewed and assessed. Controls in place.',
                    'is_active' => true,
                ])
            );
            $this->riskIds[] = $risk->id;
        }

        $this->command->info('Risks: '.count($this->riskIds).' created');
    }

    private function seedRiskActions(): void
    {
        $count = 0;
        $actionTemplates = [
            ['title' => 'Review and update control procedures', 'priority' => ActionPriority::HIGH],
            ['title' => 'Conduct staff training session', 'priority' => ActionPriority::MEDIUM],
            ['title' => 'Implement automated monitoring', 'priority' => ActionPriority::HIGH],
            ['title' => 'Update policy documentation', 'priority' => ActionPriority::LOW],
            ['title' => 'Perform gap analysis', 'priority' => ActionPriority::MEDIUM],
            ['title' => 'Engage external consultant', 'priority' => ActionPriority::HIGH],
            ['title' => 'Schedule quarterly review', 'priority' => ActionPriority::LOW],
            ['title' => 'Test disaster recovery plan', 'priority' => ActionPriority::MEDIUM],
        ];

        $statuses = [ActionStatus::OPEN, ActionStatus::IN_PROGRESS, ActionStatus::COMPLETED, ActionStatus::OVERDUE];

        foreach ($this->riskIds as $index => $riskId) {
            $numActions = ($index % 3) + 2;
            for ($i = 0; $i < $numActions; $i++) {
                $template = $actionTemplates[($index + $i) % count($actionTemplates)];
                $status = $statuses[($index + $i) % count($statuses)];
                $dueDate = now()->addDays(rand(-30, 120));

                RiskAction::firstOrCreate(
                    ['risk_id' => $riskId, 'title' => $template['title']],
                    [
                        'description' => 'Action item for risk mitigation: '.$template['title'],
                        'owner_id' => $this->userIds[array_rand($this->userIds)],
                        'due_date' => $dueDate->format('Y-m-d'),
                        'status' => $status,
                        'priority' => $template['priority'],
                        'completed_at' => $status === ActionStatus::COMPLETED ? now()->subDays(rand(1, 30))->format('Y-m-d') : null,
                    ]
                );
                $count++;
            }
        }

        $this->command->info('Risk Actions: '.$count.' created');
    }

    private function seedRiskControls(): void
    {
        if (empty($this->controlIds) || empty($this->riskIds)) {
            return;
        }

        $count = 0;
        $statuses = [ControlImplementationStatus::PLANNED, ControlImplementationStatus::IN_PROGRESS, ControlImplementationStatus::IMPLEMENTED];

        foreach ($this->riskIds as $index => $riskId) {
            $numControls = ($index % 2) + 1;
            $usedControls = [];

            for ($i = 0; $i < $numControls; $i++) {
                $controlId = $this->controlIds[($index + $i) % count($this->controlIds)];
                if (in_array($controlId, $usedControls)) {
                    continue;
                }
                $usedControls[] = $controlId;

                RiskControl::firstOrCreate(
                    ['risk_id' => $riskId, 'control_id' => $controlId],
                    [
                        'implementation_status' => $statuses[($index + $i) % count($statuses)],
                    ]
                );
                $count++;
            }
        }

        $this->command->info('Risk Controls: '.$count.' created');
    }

    private function seedTaskMilestones(): void
    {
        if (empty($this->workItemIds)) {
            $this->command->warn('No work items found, skipping additional task milestones');

            return;
        }

        $count = 0;
        $statuses = ['Not Started', 'In Progress', 'Completed'];

        // Only add milestones to work items that don't already have them
        $existingMilestoneWorkIds = TaskMilestone::pluck('work_item_id')->unique()->toArray();
        $workItemsWithoutMilestones = array_diff($this->workItemIds, $existingMilestoneWorkIds);

        foreach ($workItemsWithoutMilestones as $index => $workItemId) {
            $workItem = WorkItem::find($workItemId);
            if (! $workItem) {
                continue;
            }

            $numMilestones = ($index % 3) + 2;
            for ($i = 0; $i < $numMilestones; $i++) {
                $milestone = TaskMilestone::firstOrCreate(
                    ['work_item_id' => $workItemId, 'order' => $i],
                    [
                        'title' => 'Phase '.($i + 1).': '.['Planning', 'Implementation', 'Testing', 'Review', 'Deployment'][$i % 5],
                        'description' => 'Milestone '.($i + 1).' for '.$workItem->ref_no,
                        'target_date' => now()->addDays(30 * ($i + 1))->format('Y-m-d'),
                        'status' => $statuses[$i % 3],
                    ]
                );
                $this->milestoneIds[] = $milestone->id;
                $count++;
            }
        }

        $this->command->info('Task Milestones: '.$count.' additional created');
    }

    private function seedTaskDependencies(): void
    {
        if (count($this->workItemIds) < 4) {
            return;
        }

        $count = 0;
        $numDeps = min(20, (int) (count($this->workItemIds) / 2));
        $created = [];

        for ($i = 0; $i < $numDeps; $i++) {
            $workItemId = $this->workItemIds[$i % count($this->workItemIds)];
            $dependsOnIndex = ($i + 1 + ($i % 3)) % count($this->workItemIds);
            $dependsOnId = $this->workItemIds[$dependsOnIndex];

            if ($workItemId === $dependsOnId) {
                continue;
            }

            $key = $workItemId.'-'.$dependsOnId;
            if (in_array($key, $created)) {
                continue;
            }

            TaskDependency::firstOrCreate(
                ['work_item_id' => $workItemId, 'depends_on_id' => $dependsOnId]
            );
            $created[] = $key;
            $count++;
        }

        $this->command->info('Task Dependencies: '.$count.' created');
    }

    private function seedMilestoneAssignments(): void
    {
        if (empty($this->milestoneIds) || empty($this->userIds)) {
            return;
        }

        $count = 0;
        foreach ($this->milestoneIds as $index => $milestoneId) {
            if ($index % 2 !== 0) {
                continue;
            }
            $userId = $this->userIds[$index % count($this->userIds)];
            MilestoneAssignment::firstOrCreate(
                ['milestone_id' => $milestoneId, 'user_id' => $userId]
            );
            $count++;
        }

        $this->command->info('Milestone Assignments: '.$count.' created');
    }

    private function seedRiskWorkItems(): void
    {
        if (empty($this->riskIds) || empty($this->workItemIds)) {
            return;
        }

        $count = 0;
        $numLinks = min(20, count($this->riskIds));

        for ($i = 0; $i < $numLinks; $i++) {
            $riskId = $this->riskIds[$i % count($this->riskIds)];
            $workItemId = $this->workItemIds[$i % count($this->workItemIds)];

            RiskWorkItem::firstOrCreate(
                ['risk_id' => $riskId, 'work_item_id' => $workItemId]
            );
            $count++;
        }

        $this->command->info('Risk-WorkItem links: '.$count.' created');
    }

    private function seedRiskGovernanceItems(): void
    {
        if (empty($this->riskIds) || empty($this->governanceItemIds)) {
            return;
        }

        $count = 0;
        $numLinks = min(15, count($this->riskIds));

        for ($i = 0; $i < $numLinks; $i++) {
            $riskId = $this->riskIds[$i % count($this->riskIds)];
            $govId = $this->governanceItemIds[$i % count($this->governanceItemIds)];

            RiskGovernanceItem::firstOrCreate(
                ['risk_id' => $riskId, 'governance_item_id' => $govId]
            );
            $count++;
        }

        $this->command->info('Risk-Governance links: '.$count.' created');
    }
}
