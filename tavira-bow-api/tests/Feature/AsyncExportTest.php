<?php

use App\Jobs\ProcessExportFile;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    cache()->flush();
    $this->user = User::factory()->admin()->create();
});

it('dispatches export job and returns 202 with job_id', function () {
    Bus::fake();

    $response = $this->actingAs($this->user)
        ->getJson('/api/export/workitems');

    $response->assertStatus(202)
        ->assertJsonStructure(['message', 'job_id', 'job_status'])
        ->assertJson(['job_status' => 'queued']);

    Bus::assertDispatched(ProcessExportFile::class, function ($job) {
        return $job->type === 'workitems' && $job->userId === $this->user->id;
    });
});

it('returns export status from cache', function () {
    $jobId = 'export_test_123';
    Cache::put("export_progress_{$jobId}", [
        'status' => 'completed',
        'type' => 'workitems',
        'rows' => 42,
        'file' => 'exports/test/file.xlsx',
        'filename' => 'workitems_export.xlsx',
    ], 3600);

    $response = $this->actingAs($this->user)
        ->getJson("/api/export/status/{$jobId}");

    $response->assertOk()
        ->assertJson([
            'status' => 'completed',
            'rows' => 42,
        ]);
});

it('returns unknown for non-existent export job', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/export/status/nonexistent');

    $response->assertOk()
        ->assertJson(['status' => 'unknown']);
});

it('returns 404 when export download is not ready', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/export/download/nonexistent');

    $response->assertStatus(404);
});
