<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\SupplierContract;
use App\Models\WorkItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Get global statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get departments user has access to
        $departments = null;
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
        }

        // Work Items stats
        $workItemsQuery = WorkItem::query();
        if ($departments !== null) {
            $workItemsQuery->whereIn('department', $departments);
        }

        $workItemsTotal = $workItemsQuery->count();
        $workItemsOverdue = (clone $workItemsQuery)->overdue()->count();
        $workItemsCompleted = (clone $workItemsQuery)
            ->where('current_status', 'Completed')
            ->count();
        $workItemsPriority = (clone $workItemsQuery)->priority()->count();

        // Governance stats
        $governanceQuery = GovernanceItem::query();
        if ($departments !== null) {
            $governanceQuery->whereIn('department', $departments);
        }

        $governanceTotal = $governanceQuery->count();
        $governanceOverdue = (clone $governanceQuery)
            ->where('deadline', '<', now())
            ->whereNull('completion_date')
            ->count();

        // Contract alerts
        $contractsExpiring = SupplierContract::expiringSoon(90)->count();

        // Risk stats
        $risksTotal = Risk::active()->count();
        $risksHigh = Risk::active()->highRisk()->count();

        // Supplier stats
        $suppliersTotal = \App\Models\Supplier::active()->count();

        // RAG distribution for tasks
        $ragQuery = WorkItem::query()
            ->select('rag_status')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('rag_status')
            ->groupBy('rag_status');

        if ($departments !== null) {
            $ragQuery->whereIn('department', $departments);
        }

        $ragStats = $ragQuery->get();

        return response()->json([
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
                'blue' => $ragStats->firstWhere('rag_status', 'Blue')?->count ?? 0,
                'green' => $ragStats->firstWhere('rag_status', 'Green')?->count ?? 0,
                'amber' => $ragStats->firstWhere('rag_status', 'Amber')?->count ?? 0,
                'red' => $ragStats->firstWhere('rag_status', 'Red')?->count ?? 0,
            ],
        ]);
    }

    /**
     * Get statistics by department/area
     */
    public function byArea(Request $request): JsonResponse
    {
        $user = $request->user();

        $departments = null;
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
        }

        // Get base stats by department
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

        if ($departments !== null) {
            $query->whereIn('department', $departments);
        }

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
                'trend' => 'stable', // TODO: calculate from historical data
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
        $user = $request->user();

        $query = WorkItem::query()
            ->select('rag_status')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('rag_status')
            ->groupBy('rag_status');

        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
            $query->whereIn('department', $departments);
        }

        $stats = $query->get();

        return response()->json([
            'rag_distribution' => [
                'Blue' => $stats->firstWhere('rag_status', 'Blue')?->count ?? 0,
                'Green' => $stats->firstWhere('rag_status', 'Green')?->count ?? 0,
                'Amber' => $stats->firstWhere('rag_status', 'Amber')?->count ?? 0,
                'Red' => $stats->firstWhere('rag_status', 'Red')?->count ?? 0,
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

        // Get departments user has access to
        $departments = null;
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
        }

        // Overdue work items
        $overdueQuery = WorkItem::query()
            ->where('deadline', '<', now())
            ->whereNull('completion_date')
            ->orderBy('deadline')
            ->limit(10);

        if ($departments !== null) {
            $overdueQuery->whereIn('department', $departments);
        }

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

        // Expiring contracts
        $expiringContracts = SupplierContract::query()
            ->with('supplier')
            ->expiringSoon(90)
            ->orderBy('end_date')
            ->limit(10)
            ->get();

        foreach ($expiringContracts as $contract) {
            $alerts[] = [
                'id' => 1000 + $contract->id, // Offset to avoid ID collision
                'type' => 'expiring_contract',
                'title' => 'Contrat expirant',
                'description' => "{$contract->contract_ref} - expire dans {$contract->days_until_expiry} jours",
                'severity' => $contract->days_until_expiry <= 30 ? 'high' : 'medium',
                'link' => "/suppliers/{$contract->supplier_id}",
                'created_at' => $contract->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        // High risk items
        $highRisks = Risk::active()
            ->highRisk()
            ->with('category.theme')
            ->orderByDesc('inherent_risk_score')
            ->limit(5)
            ->get();

        foreach ($highRisks as $risk) {
            $alerts[] = [
                'id' => 2000 + $risk->id, // Offset to avoid ID collision
                'type' => 'high_risk',
                'title' => 'Risque élevé',
                'description' => "{$risk->ref_no}: {$risk->name}",
                'severity' => 'high',
                'link' => "/risks/{$risk->id}",
                'created_at' => $risk->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        // Sort by severity
        usort($alerts, function ($a, $b) {
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

        $departments = null;
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
        }

        // Work items
        $workItemsQuery = WorkItem::query()
            ->with('responsibleParty')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->where('deadline', '<=', now()->addDays($days))
            ->whereNull('completion_date')
            ->orderBy('deadline');

        if ($departments !== null) {
            $workItemsQuery->whereIn('department', $departments);
        }

        $workItems = $workItemsQuery->limit(20)->get();

        // Governance items
        $governanceQuery = GovernanceItem::query()
            ->with('responsibleParty')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->where('deadline', '<=', now()->addDays($days))
            ->whereNull('completion_date')
            ->orderBy('deadline');

        if ($departments !== null) {
            $governanceQuery->whereIn('department', $departments);
        }

        $governanceItems = $governanceQuery->limit(20)->get();

        return response()->json([
            'work_items' => $workItems->map(fn ($item) => [
                'id' => $item->id,
                'type' => 'work_item',
                'ref_no' => $item->ref_no,
                'description' => Str::limit($item->description, 100),
                'deadline' => $item->deadline?->toDateString(),
                'days_until' => now()->diffInDays($item->deadline, false),
                'department' => $item->department,
                'responsible_party' => $item->responsibleParty?->full_name,
                'rag_status' => $item->rag_status?->value,
            ]),
            'governance_items' => $governanceItems->map(fn ($item) => [
                'id' => $item->id,
                'type' => 'governance',
                'ref_no' => $item->ref_no,
                'description' => Str::limit($item->description, 100),
                'deadline' => $item->deadline?->toDateString(),
                'days_until' => now()->diffInDays($item->deadline, false),
                'department' => $item->department,
                'responsible_party' => $item->responsibleParty?->full_name,
                'rag_status' => $item->rag_status?->value,
            ]),
        ]);
    }

    /**
     * Get statistics by activity type
     */
    public function byActivity(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WorkItem::query()
            ->select('activity')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN current_status = 'Completed' THEN 1 ELSE 0 END) as completed")
            ->whereNotNull('activity')
            ->groupBy('activity');

        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
            $query->whereIn('department', $departments);
        }

        $stats = $query->get();

        return response()->json([
            'data' => $stats->map(fn ($item) => [
                'activity' => $item->activity,
                'total' => $item->total,
                'completed' => $item->completed,
                'completion_rate' => $item->total > 0
                    ? round(($item->completed / $item->total) * 100, 1)
                    : 0,
            ]),
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

        $departments = null;
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
        }

        // Work items with deadlines
        $workItemsQuery = WorkItem::query()
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$start, $end]);

        if ($departments !== null) {
            $workItemsQuery->whereIn('department', $departments);
        }

        $workItems = $workItemsQuery->get();

        // Governance items with deadlines
        $governanceQuery = GovernanceItem::query()
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$start, $end]);

        if ($departments !== null) {
            $governanceQuery->whereIn('department', $departments);
        }

        $governanceItems = $governanceQuery->get();

        $events = [];

        foreach ($workItems as $item) {
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

        $departments = null;
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();
        }

        $query = WorkItem::query();
        if ($departments !== null) {
            $query->whereIn('department', $departments);
        }

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
            'blue' => (int) ($ragStats->firstWhere('rag_status', 'Blue')?->count ?? 0),
            'green' => (int) ($ragStats->firstWhere('rag_status', 'Green')?->count ?? 0),
            'amber' => (int) ($ragStats->firstWhere('rag_status', 'Amber')?->count ?? 0),
            'red' => (int) ($ragStats->firstWhere('rag_status', 'Red')?->count ?? 0),
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
