<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GovernanceItem;
use App\Models\GovernanceMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GovernanceMilestoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $milestones = GovernanceMilestone::with(['governanceItem', 'owner'])->get();

        return response()->json($milestones);
    }

    public function forGovernanceItem(GovernanceItem $item): JsonResponse
    {
        $milestones = $item->milestones()->with('owner')->get();

        return response()->json($milestones);
    }

    public function store(Request $request, GovernanceItem $item): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $validated['governance_item_id'] = $item->id;
        $milestone = GovernanceMilestone::create($validated);

        return response()->json($milestone->load(['governanceItem', 'owner']), 201);
    }

    public function show(GovernanceMilestone $milestone): JsonResponse
    {
        return response()->json($milestone->load(['governanceItem', 'owner']));
    }

    public function update(Request $request, GovernanceMilestone $milestone): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $milestone->update($validated);

        return response()->json($milestone->load(['governanceItem', 'owner']));
    }

    public function destroy(GovernanceMilestone $milestone): JsonResponse
    {
        $milestone->delete();

        return response()->json(null, 204);
    }
}
