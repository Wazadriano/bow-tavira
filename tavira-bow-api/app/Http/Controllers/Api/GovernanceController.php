<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GovernanceItemResource;
use App\Models\GovernanceItem;
use App\Models\GovernanceItemAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class GovernanceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(GovernanceItem::class, 'governance');
    }

    /**
     * List all governance items
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = GovernanceItem::query()
            ->with(['responsibleParty', 'milestones']);

        // Filter by user access
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                // Items with explicit access
                $q->whereHas('access', function ($q2) use ($user) {
                    $q2->where('user_id', $user->id)->where('can_view', true);
                })
                // Or items in user's departments
                ->orWhereIn('department', $user->departmentPermissions()
                    ->where('can_view', true)
                    ->pluck('department'));
            });
        }

        // Filters
        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('status')) {
            $query->where('current_status', $request->status);
        }

        if ($request->has('rag_status')) {
            $query->where('rag_status', $request->rag_status);
        }

        if ($request->has('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ref_no', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('activity', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'deadline');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 25), 100);

        return GovernanceItemResource::collection($query->paginate($perPage));
    }

    /**
     * Create new governance item
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ref_no' => 'required|string|max:50|unique:governance_items,ref_no',
            'activity' => 'nullable|string|max:100',
            'description' => 'required|string',
            'frequency' => 'nullable|string',
            'location' => 'nullable|string',
            'department' => 'required|string|max:100',
            'responsible_party_id' => 'nullable|exists:users,id',
            'current_status' => 'nullable|string',
            'rag_status' => 'nullable|string',
            'deadline' => 'nullable|date',
            'tags' => 'nullable|array',
        ]);

        $item = DB::transaction(function () use ($request) {
            return GovernanceItem::create($request->all());
        });

        $item->load(['responsibleParty']);

        return response()->json([
            'message' => 'Governance item created successfully',
            'governance_item' => new GovernanceItemResource($item),
        ], 201);
    }

    /**
     * Get single governance item
     */
    public function show(GovernanceItem $governance): JsonResponse
    {
        $governance->load([
            'responsibleParty',
            'milestones',
            'attachments.uploader',
            'access.user',
        ]);

        return response()->json([
            'governance_item' => new GovernanceItemResource($governance),
        ]);
    }

    /**
     * Update governance item
     */
    public function update(Request $request, GovernanceItem $governance): JsonResponse
    {
        $request->validate([
            'ref_no' => 'sometimes|string|max:50|unique:governance_items,ref_no,' . $governance->id,
            'activity' => 'nullable|string|max:100',
            'description' => 'sometimes|string',
            'frequency' => 'nullable|string',
            'location' => 'nullable|string',
            'department' => 'sometimes|string|max:100',
            'responsible_party_id' => 'nullable|exists:users,id',
            'current_status' => 'nullable|string',
            'rag_status' => 'nullable|string',
            'deadline' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'monthly_update' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $governance->update($request->all());
        $governance->load(['responsibleParty']);

        return response()->json([
            'message' => 'Governance item updated successfully',
            'governance_item' => new GovernanceItemResource($governance),
        ]);
    }

    /**
     * Delete governance item
     */
    public function destroy(GovernanceItem $governance): JsonResponse
    {
        $governance->delete();

        return response()->json([
            'message' => 'Governance item deleted successfully',
        ]);
    }

    /**
     * Get governance dashboard statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Base query with access control
        $query = GovernanceItem::query();
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('access', function ($q2) use ($user) {
                    $q2->where('user_id', $user->id)->where('can_view', true);
                })
                ->orWhereIn('department', $user->departmentPermissions()
                    ->where('can_view', true)
                    ->pluck('department'));
            });
        }

        // Total counts
        $total = (clone $query)->count();
        $completed = (clone $query)->where('current_status', 'Completed')->count();
        $inProgress = (clone $query)->where('current_status', 'In Progress')->count();
        $pending = (clone $query)->whereNull('current_status')
            ->orWhere('current_status', 'Not Started')
            ->count();
        $overdue = (clone $query)
            ->where('deadline', '<', now())
            ->whereNull('completion_date')
            ->count();

        // By department
        $byDepartment = (clone $query)
            ->select('department')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name' => $item->department,
                'count' => (int) $item->count,
            ]);

        // By frequency
        $byFrequency = (clone $query)
            ->select('frequency')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('frequency')
            ->groupBy('frequency')
            ->orderByDesc('count')
            ->get()
            ->map(fn($item) => [
                'name' => ucfirst($item->frequency?->value ?? $item->frequency ?? 'Unknown'),
                'count' => (int) $item->count,
            ]);

        // Upcoming items
        $upcoming = (clone $query)
            ->with('responsibleParty')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->whereNull('completion_date')
            ->orderBy('deadline')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'title' => $item->activity ?? $item->description ?? $item->ref_no,
                'next_due' => $item->deadline?->toDateString(),
                'department' => $item->department,
            ]);

        return response()->json([
            'data' => [
                'total_items' => $total,
                'completed' => $completed,
                'pending' => $pending + $inProgress,
                'overdue' => $overdue,
                'by_department' => $byDepartment,
                'by_frequency' => $byFrequency,
                'by_status' => [
                    'completed' => $completed,
                    'in_progress' => $inProgress,
                    'pending' => $pending,
                    'overdue' => $overdue,
                ],
                'upcoming' => $upcoming,
            ],
        ]);
    }

    /**
     * Manage access to governance item
     */
    public function manageAccess(Request $request, GovernanceItem $governance): JsonResponse
    {
        $this->authorize('update', $governance);

        $request->validate([
            'users' => 'required|array',
            'users.*.user_id' => 'required|exists:users,id',
            'users.*.can_view' => 'required|boolean',
            'users.*.can_edit' => 'required|boolean',
        ]);

        DB::transaction(function () use ($request, $governance) {
            // Remove existing access
            $governance->access()->delete();

            // Add new access
            foreach ($request->users as $access) {
                GovernanceItemAccess::create([
                    'governance_item_id' => $governance->id,
                    'user_id' => $access['user_id'],
                    'can_view' => $access['can_view'],
                    'can_edit' => $access['can_edit'],
                ]);
            }
        });

        $governance->load('access.user');

        return response()->json([
            'message' => 'Access updated successfully',
            'access' => $governance->access,
        ]);
    }
}
