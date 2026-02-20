<?php

use App\Enums\AssignmentType;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->workItem = WorkItem::create([
        'ref_no' => 'WI-ACK-001',
        'department' => 'IT',
        'description' => 'Acknowledge test work item',
        'current_status' => 'In Progress',
    ]);
    $this->assignment = TaskAssignment::create([
        'work_item_id' => $this->workItem->id,
        'user_id' => $this->owner->id,
        'assignment_type' => AssignmentType::OWNER,
    ]);
});

it('allows user to acknowledge their own assignment', function () {
    $response = $this->actingAs($this->owner)
        ->putJson("/api/task-assignments/{$this->assignment->id}/acknowledge");

    $response->assertOk()
        ->assertJsonPath('message', 'Assignment acknowledged.')
        ->assertJsonPath('data.acknowledged_at', fn ($value) => $value !== null);

    $this->assignment->refresh();
    expect($this->assignment->acknowledged_at)->not->toBeNull();
});

it('rejects acknowledge for another users assignment', function () {
    $response = $this->actingAs($this->otherUser)
        ->putJson("/api/task-assignments/{$this->assignment->id}/acknowledge");

    $response->assertForbidden()
        ->assertJsonPath('message', 'You can only acknowledge your own assignments.');

    $this->assignment->refresh();
    expect($this->assignment->acknowledged_at)->toBeNull();
});

it('is idempotent and preserves original acknowledged date', function () {
    $firstDate = now()->subHour();
    $this->assignment->update(['acknowledged_at' => $firstDate]);

    $response = $this->actingAs($this->owner)
        ->putJson("/api/task-assignments/{$this->assignment->id}/acknowledge");

    $response->assertOk();

    $this->assignment->refresh();
    expect($this->assignment->acknowledged_at->timestamp)
        ->toBe($firstDate->timestamp);
});

it('requires authentication', function () {
    $response = $this->putJson("/api/task-assignments/{$this->assignment->id}/acknowledge");

    $response->assertUnauthorized();
});
