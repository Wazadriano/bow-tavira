<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class WeeklyCeoReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Collection $tasks,
        private readonly int $overdueCount,
        private readonly int $upcomingCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("BOW Weekly Report - {$this->overdueCount} overdue, {$this->upcomingCount} upcoming")
            ->greeting("Good morning {$notifiable->full_name},")
            ->line('Here is your weekly Book of Work summary.')
            ->line("**Overdue tasks:** {$this->overdueCount}")
            ->line("**Tasks due within 14 days:** {$this->upcomingCount}")
            ->line('---');

        foreach ($this->tasks->take(50) as $task) {
            $deadline = $task->deadline?->toDateString() ?? 'No deadline';
            $status = $task->current_status?->value ?? 'N/A';
            $responsible = $task->responsibleParty?->full_name ?? 'Unassigned';
            $dept = $task->department ?? 'N/A';
            $overdue = $task->deadline && $task->deadline->isPast() ? ' [OVERDUE]' : '';

            $mail->line("**{$task->ref_no}**{$overdue} - {$dept}");
            $mail->line("Status: {$status} | Deadline: {$deadline} | Owner: {$responsible}");
            $mail->line('');
        }

        if ($this->tasks->count() > 50) {
            $mail->line('... and '.($this->tasks->count() - 50).' more tasks.');
        }

        $mail->action('View Dashboard', config('app.frontend_url', 'http://localhost:3000').'/dashboard');

        return $mail;
    }
}
