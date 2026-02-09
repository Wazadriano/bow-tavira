<?php

namespace App\Services;

use App\Enums\CurrentStatus;
use App\Enums\RAGStatus;
use App\Models\WorkItem;
use App\Models\GovernanceItem;
use Carbon\Carbon;

class RAGCalculationService
{
    /**
     * Calculate RAG status for a work item based on deadline and progress
     */
    public function calculateWorkItemRAG(WorkItem $workItem): RAGStatus
    {
        // Completed items are always Blue
        if ($workItem->current_status === CurrentStatus::COMPLETED) {
            return RAGStatus::BLUE;
        }

        // If no deadline, return Green (no urgency)
        if (!$workItem->deadline) {
            return RAGStatus::GREEN;
        }

        $now = Carbon::now();
        $deadline = Carbon::parse($workItem->deadline);
        $daysUntilDeadline = $now->diffInDays($deadline, false);

        // Past deadline = Red
        if ($daysUntilDeadline < 0) {
            return RAGStatus::RED;
        }

        // Within 7 days = Amber
        if ($daysUntilDeadline <= 7) {
            return RAGStatus::AMBER;
        }

        // Within 14 days and not started = Amber
        if ($daysUntilDeadline <= 14 && $workItem->current_status === CurrentStatus::NOT_STARTED) {
            return RAGStatus::AMBER;
        }

        // More than 14 days = Green
        return RAGStatus::GREEN;
    }

    /**
     * Calculate RAG status for a governance item
     */
    public function calculateGovernanceRAG(GovernanceItem $item): RAGStatus
    {
        // Completed items are Blue
        if ($item->status === CurrentStatus::COMPLETED) {
            return RAGStatus::BLUE;
        }

        // If no due date, return Green
        if (!$item->due_date) {
            return RAGStatus::GREEN;
        }

        $now = Carbon::now();
        $dueDate = Carbon::parse($item->due_date);
        $daysUntilDue = $now->diffInDays($dueDate, false);

        // Past due = Red
        if ($daysUntilDue < 0) {
            return RAGStatus::RED;
        }

        // Within 7 days = Amber
        if ($daysUntilDue <= 7) {
            return RAGStatus::AMBER;
        }

        return RAGStatus::GREEN;
    }

    /**
     * Batch update RAG statuses for all work items
     */
    public function updateAllWorkItemsRAG(): int
    {
        $updated = 0;

        WorkItem::query()
            ->whereNot('current_status', 'completed')
            ->chunk(100, function ($items) use (&$updated) {
                foreach ($items as $item) {
                    $newRAG = $this->calculateWorkItemRAG($item);
                    if ($item->rag_status !== $newRAG) {
                        $item->rag_status = $newRAG;
                        $item->save();
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * Batch update RAG statuses for all governance items
     */
    public function updateAllGovernanceRAG(): int
    {
        $updated = 0;

        GovernanceItem::query()
            ->whereNot('status', 'completed')
            ->chunk(100, function ($items) use (&$updated) {
                foreach ($items as $item) {
                    $newRAG = $this->calculateGovernanceRAG($item);
                    if ($item->rag_status !== $newRAG) {
                        $item->rag_status = $newRAG;
                        $item->save();
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * Get RAG summary statistics
     */
    public function getRAGSummary(string $model = 'workitems'): array
    {
        $query = $model === 'workitems'
            ? WorkItem::query()
            : GovernanceItem::query();

        $counts = $query->selectRaw('rag_status, COUNT(*) as count')
            ->groupBy('rag_status')
            ->pluck('count', 'rag_status')
            ->toArray();

        return [
            'blue' => $counts[RAGStatus::BLUE->value] ?? 0,
            'green' => $counts[RAGStatus::GREEN->value] ?? 0,
            'amber' => $counts[RAGStatus::AMBER->value] ?? 0,
            'red' => $counts[RAGStatus::RED->value] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    /**
     * Get items at risk (Amber or Red)
     */
    public function getItemsAtRisk(string $model = 'workitems', int $limit = 10): array
    {
        $query = $model === 'workitems'
            ? WorkItem::query()
            : GovernanceItem::query();

        return $query->whereIn('rag_status', [RAGStatus::AMBER, RAGStatus::RED])
            ->orderByRaw("CASE WHEN rag_status = ? THEN 1 ELSE 2 END", [RAGStatus::RED->value])
            ->orderBy($model === 'workitems' ? 'deadline' : 'due_date')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
