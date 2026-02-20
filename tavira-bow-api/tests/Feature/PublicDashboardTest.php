<?php

use App\Models\PublicDashboardToken;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->token = PublicDashboardToken::create([
        'name' => 'Test Token',
        'token' => PublicDashboardToken::generateToken(),
        'created_by' => $this->admin->id,
        'is_active' => true,
    ]);
});

// ============================================================
// Public Dashboard Access
// ============================================================

it('returns dashboard data with valid token via bearer', function () {
    $response = $this->getJson('/api/public/dashboard', [
        'Authorization' => "Bearer {$this->token->token}",
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'work_items' => ['total', 'completed', 'overdue', 'completion_rate', 'by_rag', 'by_department'],
            'governance' => ['total', 'completed', 'overdue'],
            'risks' => ['total', 'high', 'by_theme'],
            'suppliers' => ['total', 'active', 'contracts_expiring_90d'],
            'generated_at',
        ]);
});

it('returns dashboard data with valid token via query param', function () {
    $response = $this->getJson("/api/public/dashboard?token={$this->token->token}");

    $response->assertOk()
        ->assertJsonStructure(['work_items', 'governance', 'risks', 'suppliers']);
});

it('rejects request without token', function () {
    $response = $this->getJson('/api/public/dashboard');

    $response->assertUnauthorized();
});

it('rejects request with invalid token', function () {
    $response = $this->getJson('/api/public/dashboard', [
        'Authorization' => 'Bearer invalid-token-value',
    ]);

    $response->assertUnauthorized();
});

it('rejects request with inactive token', function () {
    $this->token->update(['is_active' => false]);

    $response = $this->getJson('/api/public/dashboard', [
        'Authorization' => "Bearer {$this->token->token}",
    ]);

    $response->assertUnauthorized();
});

it('rejects request with expired token', function () {
    $this->token->update(['expires_at' => now()->subDay()]);

    $response = $this->getJson('/api/public/dashboard', [
        'Authorization' => "Bearer {$this->token->token}",
    ]);

    $response->assertUnauthorized();
});

it('updates last_used_at on successful access', function () {
    expect($this->token->last_used_at)->toBeNull();

    $this->getJson('/api/public/dashboard', [
        'Authorization' => "Bearer {$this->token->token}",
    ])->assertOk();

    $this->token->refresh();
    expect($this->token->last_used_at)->not->toBeNull();
});

// ============================================================
// Token CRUD (admin only)
// ============================================================

it('lists tokens for admin', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/public-tokens');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'token', 'is_active']]]);
});

it('creates a new token', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/admin/public-tokens', [
            'name' => 'Board Dashboard',
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'name', 'token']);

    expect(PublicDashboardToken::count())->toBe(2);
});

it('creates a token with expiry', function () {
    $response = $this->actingAs($this->admin)
        ->postJson('/api/admin/public-tokens', [
            'name' => 'Temporary Token',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ]);

    $response->assertCreated();
    expect($response->json('expires_at'))->not->toBeNull();
});

it('updates a token', function () {
    $response = $this->actingAs($this->admin)
        ->putJson("/api/admin/public-tokens/{$this->token->id}", [
            'name' => 'Renamed Token',
            'is_active' => false,
        ]);

    $response->assertOk();
    $this->token->refresh();
    expect($this->token->name)->toBe('Renamed Token');
    expect($this->token->is_active)->toBeFalse();
});

it('deletes a token', function () {
    $response = $this->actingAs($this->admin)
        ->deleteJson("/api/admin/public-tokens/{$this->token->id}");

    $response->assertNoContent();
    expect(PublicDashboardToken::count())->toBe(0);
});

it('denies member access to token CRUD', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->getJson('/api/admin/public-tokens')
        ->assertForbidden();

    $this->actingAs($member)
        ->postJson('/api/admin/public-tokens', ['name' => 'Hack'])
        ->assertForbidden();
});
