<?php

namespace App\Console\Commands;

use App\Models\TeamMember;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendOverdueNotificationsCommand extends Command
{
    protected $signature = 'bow:send-overdue-notifications';

    protected $description = 'Notify team leads and Ranjit when tasks are overdue (J+1) (RG-BOW-014)';

    public function handle(): int
    {
        $yesterday = Carbon::yesterday();
        $totalSent = 0;

        $overdueTasks = WorkItem::query()
            ->with(['responsibleParty', 'assignments.user'])
            ->whereDate('deadline', $yesterday)
            ->whereNotIn('current_status', ['Completed'])
            ->whereNull('completion_date')
            ->get();

        if ($overdueTasks->isEmpty()) {
            $this->info('No newly overdue tasks.');

            return Command::SUCCESS;
        }

        $recipients = $this->getEscalationRecipients();

        if ($recipients->isEmpty()) {
            $this->warn('No escalation recipients found (team leads or Ranjit).');

            return Command::SUCCESS;
        }

        foreach ($overdueTasks as $workItem) {
            $daysOverdue = (int) $yesterday->diffInDays(Carbon::today());

            foreach ($recipients as $user) {
                $user->notify(new TaskOverdueNotification($workItem, $daysOverdue));
                $totalSent++;
            }
        }

        $this->info("Overdue notifications sent: {$totalSent} notification(s) for {$overdueTasks->count()} task(s).");

        return Command::SUCCESS;
    }

    private function getEscalationRecipients(): \Illuminate\Support\Collection
    {
        $users = collect();

        // All team leads
        $teamLeadIds = TeamMember::where('is_lead', true)
            ->pluck('user_id')
            ->unique();

        $teamLeads = User::whereIn('id', $teamLeadIds)
            ->where('is_active', true)
            ->get();

        $users = $users->merge($teamLeads);

        // Ranjit (hardcoded escalation contact per business rules)
        $ranjit = User::where('email', 'ilike', '%ranjit%')
            ->where('is_active', true)
            ->first();

        if ($ranjit) {
            $users = $users->push($ranjit);
        }

        return $users->unique('id');
    }
}
