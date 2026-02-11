<?php

use App\Models\Risk;
use App\Models\RiskCategory;
use App\Models\RiskTheme;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\DailySummaryNotification;
use Illuminate\Support\Facades\Notification;

it('sends daily summary to admin users', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();

    WorkItem::create([
        'ref_no' => 'WI-OVERDUE-01',
        'department' => 'IT',
        'description' => 'Overdue task',
        'current_status' => 'In Progress',
        'deadline' => now()->subDays(3),
    ]);

    $this->artisan('bow:send-daily-summary')
        ->assertSuccessful();

    Notification::assertSentTo($admin, DailySummaryNotification::class);
});

it('does not send if nothing to report', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();

    $this->artisan('bow:send-daily-summary')
        ->expectsOutputToContain('Nothing to report')
        ->assertSuccessful();

    Notification::assertNotSentTo($admin, DailySummaryNotification::class);
});

it('includes correct counts in summary', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();

    // 2 overdue tasks
    WorkItem::create([
        'ref_no' => 'WI-OVD-01',
        'department' => 'IT',
        'description' => 'Overdue 1',
        'current_status' => 'In Progress',
        'deadline' => now()->subDays(5),
    ]);
    WorkItem::create([
        'ref_no' => 'WI-OVD-02',
        'department' => 'Finance',
        'description' => 'Overdue 2',
        'current_status' => 'Not Started',
        'deadline' => now()->subDay(),
    ]);

    // 1 due today
    WorkItem::create([
        'ref_no' => 'WI-TODAY-01',
        'department' => 'HR',
        'description' => 'Due today',
        'current_status' => 'In Progress',
        'deadline' => today(),
    ]);

    // 1 high risk (RED)
    $theme = RiskTheme::create([
        'code' => 'REG',
        'name' => 'Regulatory',
    ]);
    $category = RiskCategory::create([
        'theme_id' => $theme->id,
        'code' => 'P-REG-01',
        'name' => 'Compliance',
    ]);
    Risk::create([
        'ref_no' => 'R-RED-01',
        'category_id' => $category->id,
        'name' => 'High risk item',
        'inherent_rag' => 'Red',
    ]);

    // 1 expiring contract
    $supplier = Supplier::create(['name' => 'Test Supplier']);
    SupplierContract::create([
        'supplier_id' => $supplier->id,
        'contract_ref' => 'CTR-EXP-01',
        'start_date' => now()->subYear(),
        'end_date' => now()->addDays(15),
    ]);

    $this->artisan('bow:send-daily-summary')
        ->assertSuccessful();

    Notification::assertSentTo($admin, DailySummaryNotification::class, function ($notification) use ($admin) {
        $mail = $notification->toMail($admin);

        $lines = collect($mail->introLines);

        return $lines->contains('2 tasks overdue')
            && $lines->contains('1 tasks due today')
            && $lines->contains('1 high-risk items (RED)')
            && $lines->contains('1 contracts expiring within 30 days');
    });
});
