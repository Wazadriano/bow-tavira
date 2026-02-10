<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('local');
});

it('rejects requests without temp_file', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/import/confirm', [
            'type' => 'workitems',
            'column_mapping' => ['ref_no' => 'ref_no'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['temp_file']);
});

it('rejects directory traversal attempts in temp_file', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/import/confirm', [
            'temp_file' => 'imports/temp/../../etc/passwd',
            'type' => 'workitems',
            'column_mapping' => ['ref_no' => 'ref_no'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['temp_file']);
});

it('rejects temp_file outside imports/temp/ directory', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/import/confirm', [
            'temp_file' => 'storage/app/secret.csv',
            'type' => 'workitems',
            'column_mapping' => ['ref_no' => 'ref_no'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['temp_file']);
});

it('validates type parameter', function () {
    Storage::disk('local')->put('imports/temp/test.csv', 'ref_no,department,description');

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/confirm', [
            'temp_file' => 'imports/temp/test.csv',
            'type' => 'invalid_type',
            'column_mapping' => ['ref_no' => 'ref_no'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('validates column_mapping is required', function () {
    Storage::disk('local')->put('imports/temp/test.csv', 'ref_no,department,description');

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/confirm', [
            'temp_file' => 'imports/temp/test.csv',
            'type' => 'workitems',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['column_mapping']);
});

it('returns 404 for non-existent temp file', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/import/confirm', [
            'temp_file' => 'imports/temp/nonexistent-file.csv',
            'type' => 'workitems',
            'column_mapping' => ['ref_no' => 'ref_no'],
        ]);

    $response->assertStatus(404);
});

it('dispatches import job for valid request', function () {
    Storage::disk('local')->put('imports/temp/test.csv', "ref_no,department,description\nBOW-001,IT,Test");

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/confirm', [
            'temp_file' => 'imports/temp/test.csv',
            'type' => 'workitems',
            'column_mapping' => ['0' => 'ref_no', '1' => 'department', '2' => 'description'],
        ]);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'message',
            'job_id',
            'job_status',
        ]);

    expect($response->json('job_status'))->toBe('queued');
});

it('requires authentication', function () {
    $response = $this->postJson('/api/import/confirm', [
        'temp_file' => 'imports/temp/test.csv',
        'type' => 'workitems',
        'column_mapping' => ['ref_no' => 'ref_no'],
    ]);

    $response->assertStatus(401);
});
