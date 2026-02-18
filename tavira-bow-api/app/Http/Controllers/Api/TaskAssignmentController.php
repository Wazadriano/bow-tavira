<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskAssignmentResource;
use App\Models\TaskAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskAssignmentController extends Controller
{
    public function acknowledge(TaskAssignment $taskAssignment): JsonResponse
    {
        if ($taskAssignment->user_id !== Auth::id()) {
            return response()->json(['message' => 'You can only acknowledge your own assignments.'], 403);
        }

        if (! $taskAssignment->isAcknowledged()) {
            $taskAssignment->update(['acknowledged_at' => now()]);
        }

        $taskAssignment->load('user');

        return response()->json([
            'message' => 'Assignment acknowledged.',
            'data' => new TaskAssignmentResource($taskAssignment),
        ]);
    }
}
