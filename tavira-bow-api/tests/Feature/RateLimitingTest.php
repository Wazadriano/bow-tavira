<?php

use App\Models\User;
use Illuminate\Cache\RateLimiter;

beforeEach(function () {
    app(RateLimiter::class)->clear(sha1('|127.0.0.1'));
    app(RateLimiter::class)->clear(sha1('api/auth/login|127.0.0.1'));
    cache()->flush();

    $this->user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);
});

it('blocks login after 5 failed attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(429);
});

it('allows login within rate limit', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $response->assertOk();
});

it('returns rate limit headers on protected API routes', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/dashboard/stats');

    $response->assertOk()
        ->assertHeader('X-RateLimit-Limit', 60)
        ->assertHeader('X-RateLimit-Remaining');
});
