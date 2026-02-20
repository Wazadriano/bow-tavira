<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\WorkItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::remember('public_dashboard', 300, function () {
            return [
                'work_items' => $this->workItemStats(),
                'governance' => $this->governanceStats(),
                'risks' => $this->riskStats(),
                'suppliers' => $this->supplierStats(),
                'generated_at' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    private function workItemStats(): array
    {
        $total = WorkItem::count();
        $completed = WorkItem::where('current_status', 'Completed')->count();
        $overdue = WorkItem::overdue()->count();
        $inProgress = WorkItem::where('current_status', 'In Progress')->count();

        $ragStats = WorkItem::query()
            ->selectRaw('rag_status, count(*) as count')
            ->whereNotNull('rag_status')
            ->groupBy('rag_status')
            ->pluck('count', 'rag_status');

        $byDepartment = WorkItem::query()
            ->selectRaw('department, count(*) as total')
            ->selectRaw("SUM(CASE WHEN current_status = 'Completed' THEN 1 ELSE 0 END) as completed")
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderByRaw('count(*) DESC')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->department,
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
            ]);

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'by_rag' => [
                'blue' => (int) ($ragStats['Blue'] ?? 0),
                'green' => (int) ($ragStats['Green'] ?? 0),
                'amber' => (int) ($ragStats['Amber'] ?? 0),
                'red' => (int) ($ragStats['Red'] ?? 0),
            ],
            'by_department' => $byDepartment,
        ];
    }

    private function governanceStats(): array
    {
        $total = GovernanceItem::count();
        $completed = GovernanceItem::whereNotNull('completion_date')->count();
        $overdue = GovernanceItem::where('deadline', '<', now())
            ->whereNull('completion_date')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'overdue' => $overdue,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    private function riskStats(): array
    {
        $total = Risk::active()->count();
        $high = Risk::active()->highRisk()->count();

        $byTheme = \App\Models\RiskTheme::with('categories')
            ->where('is_active', true)
            ->get()
            ->map(function ($theme) {
                $count = $theme->categories->sum(fn ($c) => $c->risks()->active()->count());

                return ['name' => $theme->name, 'count' => $count];
            })
            ->filter(fn ($t) => $t['count'] > 0)
            ->values();

        return [
            'total' => $total,
            'high' => $high,
            'by_theme' => $byTheme,
        ];
    }

    private function supplierStats(): array
    {
        $total = Supplier::count();
        $active = Supplier::active()->count();
        $contractsExpiring = SupplierContract::expiringSoon(90)->count();

        return [
            'total' => $total,
            'active' => $active,
            'contracts_expiring_90d' => $contractsExpiring,
        ];
    }
}
