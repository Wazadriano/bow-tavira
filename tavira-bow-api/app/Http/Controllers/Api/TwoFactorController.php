<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly Google2FA $google2fa
    ) {}

    /**
     * Enable 2FA - generate secret and return provisioning URI
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at) {
            return response()->json(['message' => '2FA is already enabled.'], 409);
        }

        $secret = $this->google2fa->generateSecretKey();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null,
        ]);

        $qrUri = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qr_uri' => $qrUri,
        ]);
    }

    /**
     * Confirm 2FA setup with a valid TOTP code
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => '2FA setup not started.'], 422);
        }

        if ($user->two_factor_confirmed_at) {
            return response()->json(['message' => '2FA is already confirmed.'], 409);
        }

        $secret = decrypt($user->two_factor_secret);

        if (! $this->google2fa->verifyKey($secret, $request->code)) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return response()->json([
            'message' => '2FA enabled successfully.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Verify 2FA code during login
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();

        $token = $user->currentAccessToken();
        if (! $token instanceof PersonalAccessToken || $token->name !== '2fa-pending') {
            return response()->json(['message' => 'Invalid token for 2FA verification.'], 403);
        }

        $secret = decrypt($user->two_factor_secret);
        $code = $request->code;

        $valid = $this->google2fa->verifyKey($secret, $code);

        if (! $valid) {
            $valid = $this->useRecoveryCode($user, $code);
        }

        if (! $valid) {
            return response()->json(['message' => 'Invalid 2FA code.'], 422);
        }

        $user->currentAccessToken()->delete();

        $token = $user->createToken('auth-token', ['*'], now()->addDays(7));
        $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addDays(30));

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password.'], 422);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return response()->json([
            'message' => '2FA disabled successfully.',
        ]);
    }

    /**
     * Regenerate recovery codes
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (! $user->two_factor_confirmed_at) {
            return response()->json(['message' => '2FA is not enabled.'], 422);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password.'], 422);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    private function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::random(10))
            ->values()
            ->toArray();
    }

    private function useRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($code, $recoveryCodes)) {
            return false;
        }

        $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return true;
    }
}
