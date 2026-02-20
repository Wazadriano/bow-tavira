<?php

use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();
});

function createDbNotification(User $user, array $data = []): string
{
    $id = Str::uuid()->toString();
    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'App\\Notifications\\TaskAssignedNotification',
        'notifiable_type' => 'App\\Models\\User',
        'notifiable_id' => $user->id,
        'data' => json_encode(array_merge([
            'type' => 'task_assigned',
            'message' => 'You have been assigned to a task',
        ], $data)),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

it('lists notifications for authenticated user', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/notifications');

    $response->assertOk()
        ->assertJsonStructure([
            'notifications',
            'unread_count',
            'meta' => ['current_page', 'last_page', 'total'],
        ]);
});

it('returns unread count', function () {
    createDbNotification($this->admin);

    $response = $this->actingAs($this->admin)->getJson('/api/notifications/unread-count');

    $response->assertOk()
        ->assertJsonStructure(['count']);
    expect($response->json('count'))->toBe(1);
});

it('marks a notification as read', function () {
    $notifId = createDbNotification($this->admin);

    $response = $this->actingAs($this->admin)->putJson("/api/notifications/{$notifId}/read");

    $response->assertOk();
    expect($this->admin->unreadNotifications()->count())->toBe(0);
});

it('marks all notifications as read', function () {
    createDbNotification($this->admin);
    createDbNotification($this->admin);

    $response = $this->actingAs($this->admin)->putJson('/api/notifications/read-all');

    $response->assertOk();
    expect($this->admin->unreadNotifications()->count())->toBe(0);
});

it('deletes a notification', function () {
    $notifId = createDbNotification($this->admin);

    $response = $this->actingAs($this->admin)->deleteJson("/api/notifications/{$notifId}");

    $response->assertNoContent();
    expect($this->admin->notifications()->count())->toBe(0);
});

it('returns 404 for non-existent notification mark read', function () {
    $fakeUuid = Str::uuid()->toString();

    $response = $this->actingAs($this->admin)->putJson("/api/notifications/{$fakeUuid}/read");

    $response->assertNotFound();
});

it('dispatches TaskAssignedNotification when assigning user to workitem', function () {
    Notification::fake();

    $workItem = WorkItem::create([
        'ref_no' => 'WI-ASSIGN',
        'department' => 'IT',
        'description' => 'Assignment test',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)
        ->postJson("/api/workitems/{$workItem->id}/assign/{$this->member->id}");

    $response->assertStatus(201);

    Notification::assertSentTo($this->member, TaskAssignedNotification::class);
});

it('sends task due reminders via command', function () {
    Notification::fake();

    WorkItem::create([
        'ref_no' => 'WI-REMIND',
        'department' => 'IT',
        'description' => 'Reminder test',
        'current_status' => 'In Progress',
        'deadline' => now()->addDays(14),
        'responsible_party_id' => $this->member->id,
    ]);

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertSentTo($this->member, TaskDueReminderNotification::class);
});

it('does not send reminders for completed tasks', function () {
    Notification::fake();

    WorkItem::create([
        'ref_no' => 'WI-DONE',
        'department' => 'IT',
        'description' => 'Completed task',
        'current_status' => 'Completed',
        'deadline' => now()->addDays(14),
        'responsible_party_id' => $this->member->id,
    ]);

    $this->artisan('bow:send-task-reminders')
        ->assertSuccessful();

    Notification::assertNotSentTo($this->member, TaskDueReminderNotification::class);
});
