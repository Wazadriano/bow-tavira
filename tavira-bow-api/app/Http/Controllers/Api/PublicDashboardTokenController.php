<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicDashboardToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicDashboardTokenController extends Controller
{
    public function index(): JsonResponse
    {
        $tokens = PublicDashboardToken::with('creator:id,full_name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PublicDashboardToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'token' => $t->token,
                'is_active' => $t->is_active,
                'expires_at' => $t->expires_at?->toIso8601String(),
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_by' => $t->creator?->full_name,
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $tokens]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $token = PublicDashboardToken::create([
            'name' => $validated['name'],
            'token' => PublicDashboardToken::generateToken(),
            'created_by' => $request->user()->id,
            'is_active' => true,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'id' => $token->id,
            'name' => $token->name,
            'token' => $token->token,
            'expires_at' => $token->expires_at?->toIso8601String(),
        ], 201);
    }

    public function update(Request $request, PublicDashboardToken $token): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date',
        ]);

        $token->update($validated);

        return response()->json([
            'id' => $token->id,
            'name' => $token->name,
            'is_active' => $token->is_active,
            'expires_at' => $token->expires_at?->toIso8601String(),
        ]);
    }

    public function destroy(PublicDashboardToken $token): JsonResponse
    {
        $token->delete();

        return response()->json(null, 204);
    }
}
