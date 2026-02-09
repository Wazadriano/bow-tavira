<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teams = Team::with(['members.user', 'owner'])->get();
        return response()->json($teams);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_id' => 'required|exists:users,id',
        ]);

        $team = Team::create($validated);
        return response()->json($team->load(['members.user', 'owner']), 201);
    }

    public function show(Team $team): JsonResponse
    {
        return response()->json($team->load(['members.user', 'owner', 'workItems']));
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'owner_id' => 'exists:users,id',
        ]);

        $team->update($validated);
        return response()->json($team->load(['members.user', 'owner']));
    }

    public function destroy(Team $team): JsonResponse
    {
        $team->delete();
        return response()->json(null, 204);
    }
}
