<?php

namespace App\Console\Commands;

use App\Enums\CurrentStatus;
use App\Enums\RAGStatus;
use App\Models\Risk;
use App\Models\SupplierContract;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\DailySummaryNotification;
use Illuminate\Console\Command;

class SendDailySummaryCommand extends Command
{
    protected $signature = 'bow:send-daily-summary';

    protected $description = 'Send daily summary email to admins and managers';

    public function handle(): int
    {
        $overdueTasks = WorkItem::where('current_status', '!=', CurrentStatus::COMPLETED)
            ->where('deadline', '<', today())
            ->count();

        $dueToday = WorkItem::where('deadline', today())
            ->where('current_status', '!=', CurrentStatus::COMPLETED)
            ->count();

        $highRisks = Risk::where('inherent_rag', RAGStatus::RED)->count();

        $expiringContracts = SupplierContract::where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>', now())
            ->count();

        if ($overdueTasks === 0 && $dueToday === 0 && $highRisks === 0 && $expiringContracts === 0) {
            $this->info('Nothing to report.');

            return Command::SUCCESS;
        }

        $summary = [
            'overdue_tasks' => $overdueTasks,
            'due_today' => $dueToday,
            'high_risks' => $highRisks,
            'expiring_contracts' => $expiringContracts,
        ];

        $users = User::whereIn('role', ['admin', 'manager'])->get();
        $totalSent = 0;

        foreach ($users as $user) {
            $user->notify(new DailySummaryNotification($summary));
            $totalSent++;
        }

        $this->info("Daily summary sent: {$totalSent} email(s).");

        return Command::SUCCESS;
    }
}
