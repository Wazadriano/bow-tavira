<?php

use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

it('does not send notification on self-assignment', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $workItem = WorkItem::create([
        'ref_no' => 'WI-SA-1',
        'department' => 'IT',
        'description' => 'Self-assign test',
        'current_status' => 'In Progress',
    ]);

    $this->actingAs($user)
        ->postJson("/api/workitems/{$workItem->id}/assign/{$user->id}")
        ->assertStatus(201);

    Notification::assertNotSentTo($user, TaskAssignedNotification::class);
});

it('sends notification when manager assigns another user', function () {
    $manager = User::factory()->create(['role' => 'admin']);
    $assignee = User::factory()->create();
    $workItem = WorkItem::create([
        'ref_no' => 'WI-SA-2',
        'department' => 'IT',
        'description' => 'Manager assign test',
        'current_status' => 'In Progress',
    ]);

    $this->actingAs($manager)
        ->postJson("/api/workitems/{$workItem->id}/assign/{$assignee->id}")
        ->assertStatus(201);

    Notification::assertSentTo($assignee, TaskAssignedNotification::class);
});
