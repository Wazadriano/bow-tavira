<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Admin access required');
        }

        $query = LoginHistory::with('user:id,full_name,email')
            ->orderByDesc('logged_in_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('from')) {
            $query->where('logged_in_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('logged_in_at', '<=', $request->to);
        }

        $histories = $query->paginate($request->integer('per_page', 50));

        return response()->json($histories);
    }
}
