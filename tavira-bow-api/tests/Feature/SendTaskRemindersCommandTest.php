<?php

use App\Enums\AssignmentType;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

function createWorkItem(array $overrides = []): WorkItem
{
    static $counter = 0;
    $counter++;

    return WorkItem::create(array_merge([
        'ref_no' => "WI-REM-{$counter}",
        'department' => 'IT',
        'description' => 'Reminder test',
        'current_status' => 'In Progress',
    ], $overrides));
}

it('sends reminders at J-14', function () {
    $user = User::factory()->create();
    $workItem = createWorkItem([
        'deadline' => Carbon::today()->addDays(14),
    ]);
    TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'user_id' => $user->id,
        'assignment_type' => AssignmentType::MEMBER,
    ]);

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertSentTo($user, TaskDueReminderNotification::class);
});

it('sends reminders on Jour J (deadline today)', function () {
    $user = User::factory()->create();
    $workItem = createWorkItem([
        'deadline' => Carbon::today(),
    ]);
    TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'user_id' => $user->id,
        'assignment_type' => AssignmentType::MEMBER,
    ]);

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertSentTo($user, TaskDueReminderNotification::class);
});

it('does not send reminders at old J-30 J-7 J-3 intervals', function () {
    $user = User::factory()->create();

    foreach ([30, 7, 3, 1] as $days) {
        $workItem = createWorkItem([
            'deadline' => Carbon::today()->addDays($days),
        ]);
        TaskAssignment::create([
            'work_item_id' => $workItem->id,
            'user_id' => $user->id,
            'assignment_type' => AssignmentType::MEMBER,
        ]);
    }

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($user, TaskDueReminderNotification::class);
});

it('does not send reminder to user who acknowledged', function () {
    $acknowledgedUser = User::factory()->create();
    $unacknowledgedUser = User::factory()->create();
    $workItem = createWorkItem([
        'deadline' => Carbon::today()->addDays(14),
    ]);

    TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'user_id' => $acknowledgedUser->id,
        'assignment_type' => AssignmentType::MEMBER,
        'acknowledged_at' => now(),
    ]);

    TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'user_id' => $unacknowledgedUser->id,
        'assignment_type' => AssignmentType::MEMBER,
    ]);

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($acknowledgedUser, TaskDueReminderNotification::class);
    Notification::assertSentTo($unacknowledgedUser, TaskDueReminderNotification::class);
});

it('always sends reminder to responsible party regardless of acknowledgement', function () {
    $responsibleParty = User::factory()->create();
    createWorkItem([
        'deadline' => Carbon::today()->addDays(14),
        'responsible_party_id' => $responsibleParty->id,
    ]);

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertSentTo($responsibleParty, TaskDueReminderNotification::class);
});

it('does not send reminders for completed tasks', function () {
    $user = User::factory()->create();
    $workItem = createWorkItem([
        'deadline' => Carbon::today()->addDays(14),
        'current_status' => 'Completed',
    ]);
    TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'user_id' => $user->id,
        'assignment_type' => AssignmentType::MEMBER,
    ]);

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($user, TaskDueReminderNotification::class);
});
