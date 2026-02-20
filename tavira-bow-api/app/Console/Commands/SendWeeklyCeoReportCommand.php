<?php

namespace App\Console\Commands;

use App\Enums\CurrentStatus;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\WeeklyCeoReportNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendWeeklyCeoReportCommand extends Command
{
    protected $signature = 'bow:send-weekly-ceo-report';

    protected $description = 'Send weekly consolidated report to CEO with tasks due within 14 days (RG-BOW-014)';

    public function handle(): int
    {
        $cutoffDate = Carbon::today()->addDays(14);

        $tasks = WorkItem::query()
            ->with(['responsibleParty'])
            ->where('current_status', '!=', CurrentStatus::COMPLETED)
            ->whereNull('completion_date')
            ->where(function ($query) use ($cutoffDate) {
                $query->where('deadline', '<=', $cutoffDate)
                    ->whereNotNull('deadline');
            })
            ->orderBy('deadline', 'asc')
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks due within 14 days. No report to send.');

            return Command::SUCCESS;
        }

        $overdueCount = $tasks->filter(fn ($t) => $t->deadline && $t->deadline->isPast())->count();
        $upcomingCount = $tasks->count() - $overdueCount;

        $recipients = User::whereIn('role', ['admin'])
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('No admin users found for CEO report.');

            return Command::SUCCESS;
        }

        $totalSent = 0;

        foreach ($recipients as $user) {
            $user->notify(new WeeklyCeoReportNotification($tasks, $overdueCount, $upcomingCount));
            $totalSent++;
        }

        $this->info("Weekly CEO report sent to {$totalSent} recipient(s). Tasks: {$tasks->count()} ({$overdueCount} overdue, {$upcomingCount} upcoming).");

        return Command::SUCCESS;
    }
}
