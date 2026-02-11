<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkItemRequest;
use App\Http\Requests\UpdateWorkItemRequest;
use App\Http\Resources\WorkItemResource;
use App\Models\TaskAssignment;
use App\Models\TaskDependency;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkItemController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(WorkItem::class, 'workitem');
    }

    /**
     * List all work items
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = WorkItem::query()
            ->with(['responsibleParty', 'assignments.user']);

        // Non-admins only see items from departments they have access to
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();

            $query->whereIn('department', $departments);
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

        if ($request->has('bau_type')) {
            $query->where('bau_or_transformative', $request->bau_type);
        }

        if ($request->has('responsible_party_id')) {
            $query->where('responsible_party_id', $request->responsible_party_id);
        }

        if ($request->has('priority_item')) {
            $query->where('priority_item', filter_var($request->priority_item, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->overdue();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ref_no', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('activity', 'ilike', "%{$search}%");
            });
        }

        // Date filters
        if ($request->has('deadline_from')) {
            $query->where('deadline', '>=', $request->deadline_from);
        }

        if ($request->has('deadline_to')) {
            $query->where('deadline', '<=', $request->deadline_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'deadline');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 25), 100);

        return WorkItemResource::collection($query->paginate($perPage));
    }

    /**
     * Create new work item
     */
    public function store(StoreWorkItemRequest $request): JsonResponse
    {
        $this->authorize('createInDepartment', [WorkItem::class, $request->department]);

        $workItem = DB::transaction(function () use ($request) {
            $workItem = WorkItem::create($request->validated());

            // Handle tags
            if ($request->has('tags') && is_array($request->tags)) {
                $workItem->tags = $request->tags;
                $workItem->save();
            }

            return $workItem;
        });

        $workItem->load(['responsibleParty', 'assignments.user']);

        return response()->json([
            'message' => 'Work item created successfully',
            'work_item' => new WorkItemResource($workItem),
        ], 201);
    }

    /**
     * Get single work item
     */
    public function show(WorkItem $workitem): JsonResponse
    {
        $workitem->load([
            'responsibleParty',
            'assignments.user',
            'milestones',
            'dependencies.dependsOn',
            'dependentOn.workItem',
        ]);

        return response()->json([
            'work_item' => new WorkItemResource($workitem),
        ]);
    }

    /**
     * Update work item
     */
    public function update(UpdateWorkItemRequest $request, WorkItem $workitem): JsonResponse
    {
        DB::transaction(function () use ($request, $workitem) {
            $workitem->update($request->validated());

            // Handle tags
            if ($request->has('tags')) {
                $workitem->tags = $request->tags;
                $workitem->save();
            }
        });

        $workitem->load(['responsibleParty', 'assignments.user']);

        return response()->json([
            'message' => 'Work item updated successfully',
            'work_item' => new WorkItemResource($workitem),
        ]);
    }

    /**
     * Delete work item
     */
    public function destroy(WorkItem $workitem): JsonResponse
    {
        $workitem->delete();

        return response()->json([
            'message' => 'Work item deleted successfully',
        ]);
    }

    /**
     * Update status only
     */
    public function updateStatus(Request $request, WorkItem $workitem): JsonResponse
    {
        $this->authorize('updateStatus', $workitem);

        $request->validate([
            'current_status' => 'required|string',
            'rag_status' => 'sometimes|string',
            'monthly_update' => 'sometimes|string',
        ]);

        $workitem->update($request->only(['current_status', 'rag_status', 'monthly_update']));

        // Auto-set completion date
        if ($request->current_status === 'Completed' && ! $workitem->completion_date) {
            $workitem->update(['completion_date' => now()]);
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'work_item' => new WorkItemResource($workitem),
        ]);
    }

    /**
     * Get work items calendar data
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = WorkItem::query()
            ->whereNotNull('deadline')
            ->select('id', 'ref_no', 'description', 'deadline', 'rag_status', 'current_status', 'department');

        // Non-admins only see items from their departments
        if (! $user->isAdmin()) {
            $departments = $user->departmentPermissions()
                ->where('can_view', true)
                ->pluck('department')
                ->toArray();

            $query->whereIn('department', $departments);
        }

        // Date range filter
        if ($request->has('start')) {
            $query->where('deadline', '>=', $request->start);
        }

        if ($request->has('end')) {
            $query->where('deadline', '<=', $request->end);
        }

        $items = $query->get();

        return response()->json([
            'events' => $items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->ref_no.' - '.Str::limit($item->description, 50),
                'start' => $item->deadline->toDateString(),
                'color' => $this->getRagColor($item->rag_status?->value),
                'extendedProps' => [
                    'ref_no' => $item->ref_no,
                    'status' => $item->current_status,
                    'department' => $item->department,
                ],
            ]),
        ]);
    }

    /**
     * Add a dependency (work item depends on another)
     */
    public function addDependency(Request $request, WorkItem $workitem, WorkItem $dependency): JsonResponse
    {
        $this->authorize('update', $workitem);

        if ($workitem->id === $dependency->id) {
            return response()->json(['message' => 'Cannot depend on itself'], 422);
        }

        $existing = TaskDependency::where('work_item_id', $workitem->id)
            ->where('depends_on_id', $dependency->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Dependency already exists'], 409);
        }

        $dep = TaskDependency::create([
            'work_item_id' => $workitem->id,
            'depends_on_id' => $dependency->id,
        ]);

        return response()->json(['dependency' => $dep->load('dependsOn')], 201);
    }

    /**
     * Assign a user to a work item
     */
    public function assign(Request $request, WorkItem $workitem, User $user): JsonResponse
    {
        $this->authorize('update', $workitem);

        $request->validate([
            'type' => 'sometimes|string|in:owner,member',
        ]);

        $existing = TaskAssignment::where('work_item_id', $workitem->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'User already assigned'], 409);
        }

        $assignment = TaskAssignment::create([
            'work_item_id' => $workitem->id,
            'user_id' => $user->id,
            'assignment_type' => $request->get('type', 'member'),
        ]);

        $user->notify(new TaskAssignedNotification($workitem, $request->user()));

        return response()->json([
            'message' => 'User assigned successfully',
            'assignment' => $assignment->load('user'),
        ], 201);
    }

    /**
     * Unassign a user from a work item
     */
    public function unassign(WorkItem $workitem, User $user): JsonResponse
    {
        $this->authorize('update', $workitem);

        $deleted = TaskAssignment::where('work_item_id', $workitem->id)
            ->where('user_id', $user->id)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * Remove a dependency
     */
    public function removeDependency(WorkItem $workitem, WorkItem $dependency): JsonResponse
    {
        $this->authorize('update', $workitem);

        TaskDependency::where('work_item_id', $workitem->id)
            ->where('depends_on_id', $dependency->id)
            ->delete();

        return response()->json(null, 204);
    }

    private function getRagColor(?string $rag): string
    {
        return match ($rag) {
            'Blue' => '#0ea5e9',
            'Green' => '#22c55e',
            'Amber' => '#f59e0b',
            'Red' => '#ef4444',
            default => '#6b7280',
        };
    }
}
