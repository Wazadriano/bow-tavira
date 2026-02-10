<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiskActionResource;
use App\Models\Risk;
use App\Models\RiskAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiskActionController extends Controller
{
    /**
     * List all risk actions across all risks (global listing)
     */
    public function all(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = RiskAction::with('risk:id,ref_no,name');

        if (! $user->isAdmin()) {
            $themeIds = $user->riskThemePermissions()
                ->where('can_view', true)
                ->pluck('theme_id');
            $query->whereHas('risk.category', fn ($q) => $q->whereIn('theme_id', $themeIds));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhereHas('risk', fn ($rq) => $rq->where('name', 'ilike', "%{$search}%"));
            });
        }

        $query->orderByRaw("CASE WHEN status = 'Overdue' THEN 0 WHEN status = 'Open' THEN 1 WHEN status = 'In Progress' THEN 2 ELSE 3 END")
            ->orderBy('due_date', 'asc');

        return RiskActionResource::collection($query->paginate($request->per_page ?? 20));
    }

    /**
     * List actions for a risk
     */
    public function index(Request $request, Risk $risk): AnonymousResourceCollection
    {
        $this->authorize('view', $risk);

        $query = $risk->actions()->with('owner');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $actions = $query->orderBy('due_date')->get();

        return RiskActionResource::collection($actions);
    }

    /**
     * Create new action
     */
    public function store(Request $request, Risk $risk): JsonResponse
    {
        $this->authorize('update', $risk);

        $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $action = RiskAction::create([
            'risk_id' => $risk->id,
            ...$request->all(),
        ]);

        $action->load('owner');

        return response()->json([
            'message' => 'Action created successfully',
            'action' => new RiskActionResource($action),
        ], 201);
    }

    /**
     * Get single action
     */
    public function show(Risk $risk, RiskAction $action): JsonResponse
    {
        $this->authorize('view', $risk);

        if ($action->risk_id !== $risk->id) {
            abort(404);
        }

        $action->load('owner');

        return response()->json([
            'action' => new RiskActionResource($action),
        ]);
    }

    /**
     * Update action
     */
    public function update(Request $request, Risk $risk, RiskAction $action): JsonResponse
    {
        $this->authorize('update', $risk);

        if ($action->risk_id !== $risk->id) {
            abort(404);
        }

        $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'due_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $action->update($request->all());

        // Auto-set completion date if status is Completed
        if ($request->status === 'Completed' && ! $action->completion_date) {
            $action->update(['completion_date' => now()]);
        }

        $action->load('owner');

        return response()->json([
            'message' => 'Action updated successfully',
            'action' => new RiskActionResource($action),
        ]);
    }

    /**
     * Delete action
     */
    public function destroy(Risk $risk, RiskAction $action): JsonResponse
    {
        $this->authorize('update', $risk);

        if ($action->risk_id !== $risk->id) {
            abort(404);
        }

        $action->delete();

        return response()->json([
            'message' => 'Action deleted successfully',
        ]);
    }

    /**
     * Get all overdue actions
     */
    public function overdue(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = RiskAction::query()
            ->with(['risk.category.theme', 'owner'])
            ->overdue()
            ->orderBy('due_date');

        // Filter by user's theme permissions
        if (! $user->isAdmin()) {
            $themeIds = $user->riskThemePermissions()
                ->where('can_view', true)
                ->pluck('theme_id');

            $query->whereHas('risk.category', function ($q) use ($themeIds) {
                $q->whereIn('theme_id', $themeIds);
            });
        }

        return RiskActionResource::collection($query->paginate(25));
    }
}
