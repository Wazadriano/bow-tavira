<?php

namespace App\Console\Commands;

use App\Services\RAGCalculationService;
use App\Services\RiskScoringService;
use Illuminate\Console\Command;

class UpdateRAGStatuses extends Command
{
    protected $signature = 'rag:update {--type=all : Type to update (workitems, governance, risks, all)}';
    protected $description = 'Update RAG statuses for work items, governance items, and risk scores';

    public function handle(RAGCalculationService $ragService, RiskScoringService $riskService): int
    {
        $type = $this->option('type');

        $this->info('Starting RAG status update...');

        if ($type === 'all' || $type === 'workitems') {
            $this->info('Updating work items...');
            $count = $ragService->updateAllWorkItemsRAG();
            $this->info("Updated {$count} work items.");
        }

        if ($type === 'all' || $type === 'governance') {
            $this->info('Updating governance items...');
            $count = $ragService->updateAllGovernanceRAG();
            $this->info("Updated {$count} governance items.");
        }

        if ($type === 'all' || $type === 'risks') {
            $this->info('Updating risk scores...');
            $count = $riskService->updateAllRiskScores();
            $this->info("Updated {$count} risks.");
        }

        $this->info('RAG status update completed.');

        return Command::SUCCESS;
    }
}
