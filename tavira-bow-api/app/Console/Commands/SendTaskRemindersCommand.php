<?php

namespace App\Console\Commands;

use App\Models\WorkItem;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendTaskRemindersCommand extends Command
{
    protected $signature = 'bow:send-task-reminders';

    protected $description = 'Send task deadline reminders at J-30, J-7, J-3, and J-1 (RG-BOW-014)';

    public function handle(): int
    {
        $reminderDays = [30, 7, 3, 1];
        $totalSent = 0;

        foreach ($reminderDays as $days) {
            $targetDate = Carbon::today()->addDays($days);

            $workItems = WorkItem::query()
                ->with(['responsibleParty', 'assignments.user'])
                ->whereDate('deadline', $targetDate)
                ->whereNotIn('current_status', ['Completed'])
                ->get();

            foreach ($workItems as $workItem) {
                $recipients = $this->getRecipients($workItem);

                foreach ($recipients as $user) {
                    $user->notify(new TaskDueReminderNotification($workItem, $days));
                    $totalSent++;
                }
            }
        }

        $this->info("Task reminders sent: {$totalSent} notification(s).");

        return Command::SUCCESS;
    }

    private function getRecipients(WorkItem $workItem): array
    {
        $users = collect();

        // Responsible party always receives reminders (no acknowledged filter)
        if ($workItem->responsibleParty) {
            $users->push($workItem->responsibleParty);
        }

        // Assigned users: only those who have NOT acknowledged (RG-BOW-014)
        foreach ($workItem->assignments as $assignment) {
            if ($assignment->user && ! $assignment->isAcknowledged()) {
                $users->push($assignment->user);
            }
        }

        return $users->unique('id')->values()->all();
    }
}
