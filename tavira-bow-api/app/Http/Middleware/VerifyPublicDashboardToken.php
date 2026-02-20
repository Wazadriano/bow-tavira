<?php

namespace App\Http\Middleware;

use App\Models\PublicDashboardToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPublicDashboardToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->query('token');

        if (! $token) {
            return response()->json(['message' => 'Token required'], 401);
        }

        $dashboardToken = PublicDashboardToken::where('token', $token)->first();

        if (! $dashboardToken || ! $dashboardToken->isValid()) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        $dashboardToken->update(['last_used_at' => now()]);

        return $next($request);
    }
}
