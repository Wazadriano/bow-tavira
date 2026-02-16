<?php

use App\Models\LoginHistory;
use App\Models\User;

beforeEach(function () {
    cache()->flush();
    $this->admin = User::factory()->admin()->create([
        'password' => bcrypt('password123'),
    ]);
    $this->member = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);
});

it('records login history on successful login', function () {
    $this->postJson('/api/auth/login', [
        'email' => $this->admin->email,
        'password' => 'password123',
    ])->assertOk();

    $history = LoginHistory::where('user_id', $this->admin->id)->first();

    expect($history)->not()->toBeNull();
    expect($history->ip_address)->not()->toBeNull();
    expect($history->logged_in_at)->not()->toBeNull();
});

it('returns login history for admin only', function () {
    LoginHistory::create([
        'user_id' => $this->member->id,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
        'logged_in_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/login-history');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'user_id', 'ip_address', 'user_agent', 'logged_in_at']]]);
});

it('denies login history to non-admin', function () {
    $this->actingAs($this->member)
        ->getJson('/api/admin/login-history')
        ->assertStatus(403);
});

it('filters login history by user_id', function () {
    LoginHistory::create([
        'user_id' => $this->admin->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Test',
        'logged_in_at' => now(),
    ]);
    LoginHistory::create([
        'user_id' => $this->member->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'Test',
        'logged_in_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/login-history?user_id='.$this->member->id);

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['user_id'])->toBe($this->member->id);
});
