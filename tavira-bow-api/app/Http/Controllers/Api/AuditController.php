<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with('causer')
            ->latest();

        if ($request->has('log_name')) {
            $query->inLog($request->log_name);
        }

        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('causer_id')) {
            $query->causedBy($request->causer_id);
        }

        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $perPage = min($request->get('per_page', 25), 100);
        $activities = $query->paginate($perPage);

        return response()->json([
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
            ],
        ]);
    }

    public function forSubject(Request $request, string $type, int $id): JsonResponse
    {
        $modelClass = $this->resolveModelClass($type);

        if (! $modelClass) {
            return response()->json(['message' => 'Invalid subject type'], 422);
        }

        $activities = Activity::where('subject_type', $modelClass)
            ->where('subject_id', $id)
            ->with('causer')
            ->latest()
            ->paginate(25);

        return response()->json([
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        $last30Days = now()->subDays(30);

        return response()->json([
            'total_events' => Activity::count(),
            'last_30_days' => Activity::where('created_at', '>=', $last30Days)->count(),
            'by_log' => Activity::selectRaw('log_name, COUNT(*) as count')
                ->groupBy('log_name')
                ->pluck('count', 'log_name'),
            'by_event' => Activity::selectRaw('event, COUNT(*) as count')
                ->whereNotNull('event')
                ->groupBy('event')
                ->pluck('count', 'event'),
        ]);
    }

    private function resolveModelClass(string $type): ?string
    {
        $map = [
            'work_items' => \App\Models\WorkItem::class,
            'risks' => \App\Models\Risk::class,
            'suppliers' => \App\Models\Supplier::class,
            'governance' => \App\Models\GovernanceItem::class,
        ];

        return $map[$type] ?? null;
    }
}
