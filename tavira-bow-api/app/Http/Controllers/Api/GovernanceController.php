<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGovernanceItemRequest;
use App\Http\Requests\UpdateGovernanceItemRequest;
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
        $this->authorizeResource(GovernanceItem::class, 'item');
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
        if (! $user->isAdmin()) {
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
    public function store(StoreGovernanceItemRequest $request): JsonResponse
    {
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
    public function show(GovernanceItem $item): JsonResponse
    {
        $item->load([
            'responsibleParty',
            'milestones',
            'attachments.uploader',
            'access.user',
        ]);

        return response()->json([
            'governance_item' => new GovernanceItemResource($item),
        ]);
    }

    /**
     * Update governance item
     */
    public function update(UpdateGovernanceItemRequest $request, GovernanceItem $item): JsonResponse
    {
        $item->update($request->all());
        $item->load(['responsibleParty']);

        return response()->json([
            'message' => 'Governance item updated successfully',
            'governance_item' => new GovernanceItemResource($item),
        ]);
    }

    /**
     * Delete governance item
     */
    public function destroy(GovernanceItem $item): JsonResponse
    {
        $item->delete();

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
        if (! $user->isAdmin()) {
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
            ->map(fn ($item) => [
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
            ->map(fn ($item) => [
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
            ->map(fn ($item) => [
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

    /**
     * Add access for a user to a governance item (route: POST governance/items/{item}/access)
     */
    public function addAccess(Request $request, GovernanceItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'access_level' => 'required|in:read,write,admin',
        ]);

        $canEdit = $validated['access_level'] !== 'read';
        $access = $item->access()->create([
            'user_id' => $validated['user_id'],
            'can_view' => true,
            'can_edit' => $canEdit,
        ]);

        return response()->json(['access' => $access->load('user')], 201);
    }

    /**
     * Remove access from a governance item (route: DELETE governance/items/{item}/access/{access})
     */
    public function removeAccess(GovernanceItem $item, GovernanceItemAccess $access): JsonResponse
    {
        $this->authorize('update', $item);

        if ($access->governance_item_id !== $item->id) {
            return response()->json(['message' => 'Access does not belong to this item'], 403);
        }

        $access->delete();

        return response()->json(null, 204);
    }
}
