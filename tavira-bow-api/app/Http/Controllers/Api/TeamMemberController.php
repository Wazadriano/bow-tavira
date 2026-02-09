<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function index(Team $team): JsonResponse
    {
        $members = $team->members()->with('user')->get();

        return response()->json($members);
    }

    public function store(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|max:100',
        ]);

        $member = $team->members()->create($validated);

        return response()->json($member->load('user'), 201);
    }

    public function destroy(Team $team, TeamMember $member): JsonResponse
    {
        $member->delete();

        return response()->json(null, 204);
    }
}
