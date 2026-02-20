<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\SupplierContract;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Scope work items to only those the user owns or is assigned to.
     */
    private function scopeWorkItems(Builder $query, User $user): Builder
    {
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('responsible_party_id', $user->id)
                    ->orWhereHas('assignments', fn ($sub) => $sub->where('user_id', $user->id));
            });
        }

        return $query;
    }

    /**
     * Scope governance items to only those the user has explicit access to or owns.
     */
    private function scopeGovernance(Builder $query, User $user): Builder
    {
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('access', fn ($q2) => $q2->where('user_id', $user->id)->where('can_view', true))
                    ->orWhere('responsible_party_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * Scope risks to only those the user owns or is responsible for.
     */
    private function scopeRisks(Builder $query, User $user): Builder
    {
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('responsible_party_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * Scope suppliers to only those the user has explicit access to or owns.
     */
    private function scopeSuppliers(Builder $query, User $user): Builder
    {
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('access', fn ($q2) => $q2->where('user_id', $user->id)->where('can_view', true))
                    ->orWhere('responsible_party_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * Get global statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = 'dashboard_stats_'.($user->isAdmin() ? 'admin' : $user->id);

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            $workItemsQuery = $this->scopeWorkItems(WorkItem::query(), $user);

            $workItemsTotal = (clone $workItemsQuery)->count();
            $workItemsOverdue = (clone $workItemsQuery)->overdue()->count();
            $workItemsCompleted = (clone $workItemsQuery)
                ->where('current_status', 'Completed')
                ->count();
            $workItemsPriority = (clone $workItemsQuery)->priority()->count();

            $governanceQuery = $this->scopeGovernance(GovernanceItem::query(), $user);
            $governanceTotal = (clone $governanceQuery)->count();
            $governanceOverdue = (clone $governanceQuery)
                ->where('deadline', '<', now())
                ->whereNull('completion_date')
                ->count();

            $riskQuery = $this->scopeRisks(Risk::active(), $user);
            $risksTotal = (clone $riskQuery)->count();
            $risksHigh = (clone $riskQuery)->highRisk()->count();

            $supplierQuery = $this->scopeSuppliers(\App\Models\Supplier::active(), $user);
            $suppliersTotal = $supplierQuery->count();

            $contractQuery = SupplierContract::query();
            if (! $user->isAdmin()) {
                $accessibleSupplierIds = \App\Models\Supplier::query()
                    ->where(function ($q) use ($user) {
                        $q->whereHas('access', fn ($q2) => $q2->where('user_id', $user->id)->where('can_view', true))
                            ->orWhere('responsible_party_id', $user->id);
                    })->pluck('id');
                $contractQuery->whereIn('supplier_id', $accessibleSupplierIds);
            }
            $contractsExpiring = (clone $contractQuery)->expiringSoon(90)->count();

            $ragQuery = $this->scopeWorkItems(
                WorkItem::query()
                    ->select('rag_status')
                    ->selectRaw('COUNT(*) as count')
                    ->whereNotNull('rag_status')
                    ->groupBy('rag_status'),
                $user
            );
            $ragStats = $ragQuery->get();

            return [
                'total_tasks' => $workItemsTotal,
                'completed_tasks' => $workItemsCompleted,
                'overdue_tasks' => $workItemsOverdue,
                'priority_tasks' => $workItemsPriority,
                'total_suppliers' => $suppliersTotal,
                'total_risks' => $risksTotal,
                'high_risks' => $risksHigh,
                'total_governance' => $governanceTotal,
                'overdue_governance' => $governanceOverdue,
                'expiring_contracts' => $contractsExpiring,
                'tasks_by_rag' => [
                    'blue' => (int) ($ragStats->firstWhere('rag_status', 'Blue')->count ?? 0),
                    'green' => (int) ($ragStats->firstWhere('rag_status', 'Green')->count ?? 0),
                    'amber' => (int) ($ragStats->firstWhere('rag_status', 'Amber')->count ?? 0),
                    'red' => (int) ($ragStats->firstWhere('rag_status', 'Red')->count ?? 0),
                ],
            ];
        });

        return response()->json($data);
    }

    /**
     * Get statistics by department/area
     */
    public function byArea(Request $request): JsonResponse
    {
        $query = WorkItem::query()
            ->select('department')
            ->selectRaw('COUNT(*) as total_tasks')
            ->selectRaw("SUM(CASE WHEN current_status = 'Completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN current_status = 'In Progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw('SUM(CASE WHEN deadline < NOW() AND completion_date IS NULL THEN 1 ELSE 0 END) as overdue')
            ->selectRaw("SUM(CASE WHEN rag_status = 'Blue' THEN 1 ELSE 0 END) as rag_blue")
            ->selectRaw("SUM(CASE WHEN rag_status = 'Green' THEN 1 ELSE 0 END) as rag_green")
            ->selectRaw("SUM(CASE WHEN rag_status = 'Amber' THEN 1 ELSE 0 END) as rag_amber")
            ->selectRaw("SUM(CASE WHEN rag_status = 'Red' THEN 1 ELSE 0 END) as rag_red")
            ->whereNotNull('department')
            ->groupBy('department');

        $this->scopeWorkItems($query, $request->user());

        $stats = $query->get();

        return response()->json([
            'data' => $stats->map(fn ($item) => [
                'department' => $item->department,
                'total_tasks' => (int) $item->total_tasks,
                'completed' => (int) $item->completed,
                'in_progress' => (int) $item->in_progress,
                'overdue' => (int) $item->overdue,
                'completion_rate' => $item->total_tasks > 0
                    ? round(($item->completed / $item->total_tasks) * 100, 1)
                    : 0,
                'trend' => 'stable',
                'rag_distribution' => [
                    'blue' => (int) $item->rag_blue,
                    'green' => (int) $item->rag_green,
                    'amber' => (int) $item->rag_amber,
                    'red' => (int) $item->rag_red,
                ],
            ]),
        ]);
    }

    /**
     * Get RAG distribution
     */
    public function byRag(Request $request): JsonResponse
    {
        $query = WorkItem::query()
            ->select('rag_status')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('rag_status')
            ->groupBy('rag_status');

        $this->scopeWorkItems($query, $request->user());

        $stats = $query->get();

        return response()->json([
            'rag_distribution' => [
                'Blue' => (int) ($stats->firstWhere('rag_status', 'Blue')->count ?? 0),
                'Green' => (int) ($stats->firstWhere('rag_status', 'Green')->count ?? 0),
                'Amber' => (int) ($stats->firstWhere('rag_status', 'Amber')->count ?? 0),
                'Red' => (int) ($stats->firstWhere('rag_status', 'Red')->count ?? 0),
            ],
        ]);
    }

    /**
     * Get alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        $user = $request->user();
        $alerts = [];

        // Overdue work items
        $overdueQuery = WorkItem::query()
            ->where('deadline', '<', now())
            ->whereNull('completion_date')
            ->orderBy('deadline')
            ->limit(10);

        $this->scopeWorkItems($overdueQuery, $user);

        $overdueItems = $overdueQuery->get();

        foreach ($overdueItems as $item) {
            $alerts[] = [
                'id' => $item->id,
                'type' => 'overdue_task',
                'title' => 'Tâche en retard',
                'description' => "{$item->ref_no} - ".Str::limit($item->description, 50),
                'severity' => 'high',
                'link' => "/tasks/{$item->id}",
                'created_at' => $item->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        // Expiring contracts (scoped by supplier access)
        $expiringContractsQuery = SupplierContract::query()
            ->with('supplier')
            ->expiringSoon(90)
            ->orderBy('end_date')
            ->limit(10);

        if (! $user->isAdmin()) {
            $accessibleSupplierIds = \App\Models\Supplier::query()
                ->where(function ($q) use ($user) {
                    $q->whereHas('access', fn ($q2) => $q2->where('user_id', $user->id)->where('can_view', true))
                        ->orWhere('responsible_party_id', $user->id);
                })->pluck('id');
            $expiringContractsQuery->whereIn('supplier_id', $accessibleSupplierIds);
        }

        $expiringContracts = $expiringContractsQuery->get();

        foreach ($expiringContracts as $contract) {
            $alerts[] = [
                'id' => 1000 + $contract->id,
                'type' => 'expiring_contract',
                'title' => 'Contrat expirant',
                'description' => "{$contract->contract_ref} - expire dans {$contract->days_until_expiry} jours",
                'severity' => $contract->days_until_expiry <= 30 ? 'high' : 'medium',
                'link' => "/suppliers/{$contract->supplier_id}",
                'created_at' => $contract->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        // High risk items
        $highRisksQuery = Risk::active()
            ->highRisk()
            ->with('category.theme')
            ->orderByDesc('inherent_risk_score')
            ->limit(5);

        $this->scopeRisks($highRisksQuery, $user);

        $highRisks = $highRisksQuery->get();

        foreach ($highRisks as $risk) {
            $alerts[] = [
                'id' => 2000 + $risk->id,
                'type' => 'high_risk',
                'title' => 'Risque élevé',
                'description' => "{$risk->ref_no}: {$risk->name}",
                'severity' => 'high',
                'link' => "/risks/{$risk->id}",
                'created_at' => $risk->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        // Sort by severity
        usort($alerts, function (array $a, array $b): int {
            $severityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];

            return ($severityOrder[$a['severity']] ?? 3) <=> ($severityOrder[$b['severity']] ?? 3);
        });

        return response()->json([
            'data' => array_slice($alerts, 0, 20),
            'total' => count($alerts),
        ]);
    }

    /**
     * Get upcoming deadlines
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = $request->get('days', 30);

        // Work items
        $workItemsQuery = WorkItem::query()
            ->with('responsibleParty')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->where('deadline', '<=', now()->addDays($days))
            ->whereNull('completion_date')
            ->orderBy('deadline');

        $this->scopeWorkItems($workItemsQuery, $user);

        $workItems = $workItemsQuery->limit(20)->get();

        // Governance items
        $governanceQuery = GovernanceItem::query()
            ->with('responsibleParty')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->where('deadline', '<=', now()->addDays($days))
            ->whereNull('completion_date')
            ->orderBy('deadline');

        $this->scopeGovernance($governanceQuery, $user);

        $governanceItems = $governanceQuery->limit(20)->get();

        return response()->json([
            'work_items' => $workItems->map(function ($item) {
                /** @var WorkItem $item */
                return [
                    'id' => $item->id,
                    'type' => 'work_item',
                    'ref_no' => $item->ref_no,
                    'description' => Str::limit($item->description, 100),
                    'deadline' => $item->deadline?->toDateString(),
                    'days_until' => now()->diffInDays($item->deadline, false),
                    'department' => $item->department,
                    'responsible_party' => $item->responsibleParty?->full_name,
                    'rag_status' => $item->rag_status?->value,
                ];
            }),
            'governance_items' => $governanceItems->map(function ($item) {
                /** @var GovernanceItem $item */
                return [
                    'id' => $item->id,
                    'type' => 'governance',
                    'ref_no' => $item->ref_no,
                    'description' => Str::limit($item->description, 100),
                    'deadline' => $item->deadline?->toDateString(),
                    'days_until' => now()->diffInDays($item->deadline, false),
                    'department' => $item->department,
                    'responsible_party' => $item->responsibleParty?->full_name,
                    'rag_status' => $item->rag_status?->value,
                ];
            }),
        ]);
    }

    /**
     * Get statistics by activity type
     */
    public function byActivity(Request $request): JsonResponse
    {
        $query = WorkItem::query()
            ->select('activity')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN current_status = 'Completed' THEN 1 ELSE 0 END) as completed")
            ->whereNotNull('activity')
            ->groupBy('activity');

        $this->scopeWorkItems($query, $request->user());

        $stats = $query->get();

        return response()->json([
            'data' => $stats->map(function ($item) {
                /** @var WorkItem $item */
                return [
                    'activity' => $item->activity,
                    'total' => $item->total,
                    'completed' => $item->completed,
                    'completion_rate' => $item->total > 0
                        ? round(($item->completed / $item->total) * 100, 1)
                        : 0,
                ];
            }),
        ]);
    }

    /**
     * Get calendar events
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = $request->user();
        $start = $request->get('start', now()->startOfMonth()->toDateString());
        $end = $request->get('end', now()->endOfMonth()->toDateString());

        // Work items with deadlines
        $workItemsQuery = WorkItem::query()
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$start, $end]);

        $this->scopeWorkItems($workItemsQuery, $user);

        $workItems = $workItemsQuery->get();

        // Governance items with deadlines
        $governanceQuery = GovernanceItem::query()
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$start, $end]);

        $this->scopeGovernance($governanceQuery, $user);

        $governanceItems = $governanceQuery->get();

        $events = [];

        foreach ($workItems as $item) {
            /** @var WorkItem $item */
            $events[] = [
                'id' => 'wi_'.$item->id,
                'title' => $item->description ?? $item->ref_no,
                'date' => $item->deadline?->toDateString(),
                'type' => 'work_item',
                'rag_status' => $item->rag_status?->value,
                'department' => $item->department,
            ];
        }

        foreach ($governanceItems as $item) {
            /** @var GovernanceItem $item */
            $events[] = [
                'id' => 'gov_'.$item->id,
                'title' => $item->name ?? $item->ref_no,
                'date' => $item->deadline?->toDateString(),
                'type' => 'governance',
                'rag_status' => $item->rag_status?->value,
                'department' => $item->department,
            ];
        }

        return response()->json([
            'data' => $events,
        ]);
    }

    /**
     * Get comprehensive tasks dashboard statistics
     */
    public function tasksDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WorkItem::query();
        $this->scopeWorkItems($query, $user);

        // Basic counts
        $total = (clone $query)->count();
        $completed = (clone $query)->where('current_status', 'Completed')->count();
        $inProgress = (clone $query)->where('current_status', 'In Progress')->count();
        $notStarted = (clone $query)->where('current_status', 'Not Started')->count();
        $overdue = (clone $query)->overdue()->count();
        $priorityCount = (clone $query)->where('priority_item', true)->count();

        // By department with priority
        $byDepartment = (clone $query)
            ->select('department')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN priority_item = true THEN 1 ELSE 0 END) as priority')
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->department,
                'total' => (int) $item->total,
                'priority' => (int) $item->priority,
            ]);

        // By activity
        $byActivity = (clone $query)
            ->select('activity')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('activity')
            ->groupBy('activity')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->activity,
                'count' => (int) $item->count,
            ]);

        // RAG distribution
        $ragStats = (clone $query)
            ->select('rag_status')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('rag_status')
            ->groupBy('rag_status')
            ->get();

        $byRag = [
            'blue' => (int) ($ragStats->firstWhere('rag_status', 'Blue')->count ?? 0),
            'green' => (int) ($ragStats->firstWhere('rag_status', 'Green')->count ?? 0),
            'amber' => (int) ($ragStats->firstWhere('rag_status', 'Amber')->count ?? 0),
            'red' => (int) ($ragStats->firstWhere('rag_status', 'Red')->count ?? 0),
        ];

        // Priority by department (top 5)
        $priorityByDept = (clone $query)
            ->select('department')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN priority_item = true THEN 1 ELSE 0 END) as priority')
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderByDesc('priority')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'department' => $item->department,
                'total' => (int) $item->total,
                'priority' => (int) $item->priority,
            ]);

        return response()->json([
            'data' => [
                'total_tasks' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'not_started' => $notStarted,
                'overdue' => $overdue,
                'priority_count' => $priorityCount,
                'by_department' => $byDepartment,
                'by_activity' => $byActivity,
                'by_rag' => $byRag,
                'priority_by_dept' => $priorityByDept,
            ],
        ]);
    }
}
