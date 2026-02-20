<?php

use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\WeeklyCeoReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    WorkItem::query()->delete();
});

it('sends weekly report to admin users', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    WorkItem::create([
        'ref_no' => 'WI-CEO-1',
        'department' => 'Finance',
        'description' => 'Task due soon',
        'current_status' => 'In Progress',
        'deadline' => Carbon::today()->addDays(7),
    ]);

    $this->artisan('bow:send-weekly-ceo-report')
        ->assertSuccessful();

    Notification::assertSentTo($admin, WeeklyCeoReportNotification::class);
});

it('does not send report when no tasks due within 14 days', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    WorkItem::create([
        'ref_no' => 'WI-CEO-2',
        'department' => 'Finance',
        'description' => 'Task due far away',
        'current_status' => 'In Progress',
        'deadline' => Carbon::today()->addDays(30),
    ]);

    $this->artisan('bow:send-weekly-ceo-report')
        ->assertSuccessful();

    Notification::assertNotSentTo($admin, WeeklyCeoReportNotification::class);
});

it('includes overdue tasks in report', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    WorkItem::create([
        'ref_no' => 'WI-CEO-3',
        'department' => 'IT',
        'description' => 'Overdue task',
        'current_status' => 'In Progress',
        'deadline' => Carbon::yesterday(),
    ]);

    $this->artisan('bow:send-weekly-ceo-report')
        ->assertSuccessful();

    Notification::assertSentTo($admin, WeeklyCeoReportNotification::class);
});

it('excludes completed tasks from report', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

    WorkItem::create([
        'ref_no' => 'WI-CEO-4',
        'department' => 'Finance',
        'description' => 'Completed task',
        'current_status' => 'Completed',
        'deadline' => Carbon::today()->addDays(5),
        'completion_date' => Carbon::yesterday(),
    ]);

    $this->artisan('bow:send-weekly-ceo-report')
        ->assertSuccessful();

    Notification::assertNotSentTo($admin, WeeklyCeoReportNotification::class);
});

it('does not send to non-admin users', function () {
    $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $member = User::factory()->create(['role' => 'member', 'is_active' => true]);

    WorkItem::create([
        'ref_no' => 'WI-CEO-5',
        'department' => 'Finance',
        'description' => 'Task due soon',
        'current_status' => 'In Progress',
        'deadline' => Carbon::today()->addDays(7),
    ]);

    $this->artisan('bow:send-weekly-ceo-report')
        ->assertSuccessful();

    Notification::assertSentTo($admin, WeeklyCeoReportNotification::class);
    Notification::assertNotSentTo($member, WeeklyCeoReportNotification::class);
});
