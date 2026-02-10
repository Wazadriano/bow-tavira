<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns unknown status for non-existent job', function () {
    $jobId = Str::uuid()->toString();

    $response = $this->actingAs($this->user)
        ->getJson("/api/import/status/{$jobId}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'unknown',
        ]);
});

it('returns progress data for existing job', function () {
    $jobId = Str::uuid()->toString();

    $progressData = [
        'status' => 'processing',
        'progress' => 45,
        'total' => 100,
        'processed' => 45,
        'successful' => 40,
        'failed' => 5,
        'errors' => [
            ['row' => 10, 'message' => 'Invalid department code'],
            ['row' => 23, 'message' => 'Missing required field: ref_no'],
        ],
    ];

    Cache::put("import_progress_{$jobId}", $progressData, now()->addHour());

    $response = $this->actingAs($this->user)
        ->getJson("/api/import/status/{$jobId}");

    $response->assertStatus(200)
        ->assertJson($progressData);
});
