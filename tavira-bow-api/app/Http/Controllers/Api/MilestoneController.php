<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskMilestone;
use App\Models\WorkItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TaskMilestone::with(['workItem', 'assignments.user']);

        if ($request->has('work_item_id')) {
            $query->where('work_item_id', $request->work_item_id);
        }

        return response()->json($query->get());
    }

    public function forWorkItem(WorkItem $workitem): JsonResponse
    {
        $milestones = $workitem->milestones()->with('assignments.user')->get();

        return response()->json($milestones);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_item_id' => 'required|exists:work_items,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        // Map API field to DB column (table has target_date, not due_date)
        if (isset($validated['due_date'])) {
            $validated['target_date'] = $validated['due_date'];
            unset($validated['due_date']);
        } else {
            $validated['target_date'] = now()->format('Y-m-d');
        }
        $validated['status'] = $validated['status'] ?? 'Not Started';

        $milestone = TaskMilestone::create($validated);

        return response()->json($milestone->load(['workItem', 'assignments.user']), 201);
    }

    public function show(TaskMilestone $milestone): JsonResponse
    {
        return response()->json($milestone->load(['workItem', 'assignments.user']));
    }

    public function update(Request $request, TaskMilestone $milestone): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string',
            'is_completed' => 'nullable|boolean',
        ]);

        // Map API field to DB column
        if (array_key_exists('due_date', $validated)) {
            $validated['target_date'] = $validated['due_date'];
            unset($validated['due_date']);
        }
        if (array_key_exists('is_completed', $validated)) {
            $validated['status'] = $validated['is_completed'] ? 'Completed' : 'Not Started';
            unset($validated['is_completed']);
        }

        $milestone->update($validated);

        return response()->json($milestone->load(['workItem', 'assignments.user']));
    }

    public function destroy(TaskMilestone $milestone): JsonResponse
    {
        $milestone->delete();

        return response()->json(null, 204);
    }
}
