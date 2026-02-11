<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->name === '2fa-pending') {
            return response()->json([
                'message' => '2FA verification required.',
                'requires_2fa' => true,
            ], 403);
        }

        return $next($request);
    }
}
