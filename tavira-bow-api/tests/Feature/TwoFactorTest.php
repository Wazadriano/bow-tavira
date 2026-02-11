<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
});

it('enables 2FA and returns secret and QR URI', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/auth/2fa/enable');

    $response->assertOk()
        ->assertJsonStructure(['secret', 'qr_uri']);

    $this->user->refresh();
    expect($this->user->two_factor_secret)->not()->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

it('rejects enable when 2FA already confirmed', function () {
    $this->user->update(['two_factor_confirmed_at' => now()]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/auth/2fa/enable');

    $response->assertStatus(409);
});

it('confirms 2FA with valid code', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $this->user->update([
        'two_factor_secret' => encrypt($secret),
    ]);

    $code = $google2fa->getCurrentOtp($secret);

    $response = $this->actingAs($this->user)
        ->postJson('/api/auth/2fa/confirm', ['code' => $code]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'recovery_codes']);

    $this->user->refresh();
    expect($this->user->two_factor_confirmed_at)->not()->toBeNull();
});

it('rejects invalid 2FA confirmation code', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $this->user->update([
        'two_factor_secret' => encrypt($secret),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/auth/2fa/confirm', ['code' => '000000']);

    $response->assertStatus(422);
});

it('login returns requires_2fa when 2FA is enabled', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $this->user->update([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJson(['requires_2fa' => true])
        ->assertJsonStructure(['token']);
});

it('verifies 2FA code and returns full tokens', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $this->user->update([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['RECOVERY1'])),
    ]);

    $token = $this->user->createToken('2fa-pending', ['2fa-pending'], now()->addMinutes(15));

    $code = $google2fa->getCurrentOtp($secret);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/auth/2fa/verify', ['code' => $code]);

    $response->assertOk()
        ->assertJsonStructure(['user', 'token', 'refresh_token']);
});

it('uses recovery code when TOTP is unavailable', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();
    $recoveryCodes = ['ABCDE12345', 'FGHIJ67890'];

    $this->user->update([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
    ]);

    $token = $this->user->createToken('2fa-pending', ['2fa-pending'], now()->addMinutes(15));

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/auth/2fa/verify', ['code' => 'ABCDE12345']);

    $response->assertOk()
        ->assertJsonStructure(['user', 'token']);

    $this->user->refresh();
    $remaining = json_decode(decrypt($this->user->two_factor_recovery_codes), true);
    expect($remaining)->not()->toContain('ABCDE12345');
    expect($remaining)->toContain('FGHIJ67890');
});

it('disables 2FA with valid password', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $this->user->update([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([])),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/auth/2fa/disable', ['password' => 'password']);

    $response->assertOk();

    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

it('blocks 2fa-pending token from protected routes', function () {
    $token = $this->user->createToken('2fa-pending', ['2fa-pending'], now()->addMinutes(15));

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/users');

    $response->assertStatus(403)
        ->assertJson(['requires_2fa' => true]);
});

it('regenerates recovery codes', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $this->user->update([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['OLD_CODE'])),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/auth/2fa/recovery-codes', ['password' => 'password']);

    $response->assertOk()
        ->assertJsonStructure(['recovery_codes']);

    $codes = $response->json('recovery_codes');
    expect($codes)->toHaveCount(8);
    expect($codes)->not()->toContain('OLD_CODE');
});
