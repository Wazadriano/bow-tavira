<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    WorkItem::query()->delete();
    TeamMember::query()->delete();
});

it('sends overdue notifications to team leads for tasks past deadline', function () {
    $teamLead = User::factory()->create(['is_active' => true]);
    $team = Team::create(['name' => 'Test Team', 'is_active' => true]);
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $teamLead->id,
        'is_lead' => true,
    ]);

    WorkItem::create([
        'ref_no' => 'WI-OVD-1',
        'department' => 'IT',
        'description' => 'Overdue task',
        'current_status' => 'In Progress',
        'deadline' => Carbon::yesterday(),
    ]);

    $this->artisan('bow:send-overdue-notifications')
        ->assertSuccessful();

    Notification::assertSentTo($teamLead, TaskOverdueNotification::class);
});

it('does not send notifications for completed tasks', function () {
    $teamLead = User::factory()->create(['is_active' => true]);
    $team = Team::create(['name' => 'Test Team', 'is_active' => true]);
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $teamLead->id,
        'is_lead' => true,
    ]);

    WorkItem::create([
        'ref_no' => 'WI-OVD-2',
        'department' => 'IT',
        'description' => 'Completed task',
        'current_status' => 'Completed',
        'deadline' => Carbon::yesterday(),
        'completion_date' => Carbon::yesterday(),
    ]);

    $this->artisan('bow:send-overdue-notifications')
        ->assertSuccessful();

    Notification::assertNotSentTo($teamLead, TaskOverdueNotification::class);
});

it('does not send notifications when no tasks are overdue', function () {
    $teamLead = User::factory()->create(['is_active' => true]);
    $team = Team::create(['name' => 'Test Team', 'is_active' => true]);
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $teamLead->id,
        'is_lead' => true,
    ]);

    $this->artisan('bow:send-overdue-notifications')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

it('sends to ranjit when found by email', function () {
    // Use existing ranjit from seeded data or create one
    $ranjit = User::where('email', 'ilike', '%ranjit%')->first();
    if (! $ranjit) {
        $ranjit = User::factory()->create([
            'email' => 'ranjit@ohadja.com',
            'is_active' => true,
        ]);
    } else {
        $ranjit->update(['is_active' => true]);
    }

    WorkItem::create([
        'ref_no' => 'WI-OVD-3',
        'department' => 'IT',
        'description' => 'Overdue task for ranjit',
        'current_status' => 'In Progress',
        'deadline' => Carbon::yesterday(),
    ]);

    $this->artisan('bow:send-overdue-notifications')
        ->assertSuccessful();

    Notification::assertSentTo($ranjit, TaskOverdueNotification::class);
});
