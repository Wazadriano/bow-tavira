<?php

namespace App\Notifications;

use App\Models\WorkItem;
use App\Services\ICalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly WorkItem $workItem,
        private readonly int $daysUntilDue
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = match (true) {
            $this->daysUntilDue <= 1 => 'URGENT: ',
            $this->daysUntilDue <= 3 => 'Reminder: ',
            default => '',
        };

        $mail = (new MailMessage)
            ->subject("{$urgency}Task {$this->workItem->ref_no} due in {$this->daysUntilDue} day(s)")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("Task **{$this->workItem->ref_no}** is due in {$this->daysUntilDue} day(s).")
            ->line('Description: '.$this->workItem->description)
            ->line('Department: '.$this->workItem->department)
            ->line('Current status: '.($this->workItem->current_status?->value ?? 'N/A'))
            ->line('Please acknowledge this task on the platform if you have not already done so.')
            ->action('View Task', url("/tasks/{$this->workItem->id}"));

        if ($this->workItem->deadline) {
            $icsContent = app(ICalendarService::class)->generateTaskEvent($this->workItem);
            $tmpFile = tempnam(sys_get_temp_dir(), 'ics_');
            file_put_contents($tmpFile, $icsContent);

            $mail->attach($tmpFile, [
                'as' => 'task-deadline.ics',
                'mime' => 'text/calendar',
            ]);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_due_reminder',
            'work_item_id' => $this->workItem->id,
            'ref_no' => $this->workItem->ref_no,
            'description' => $this->workItem->description,
            'days_until_due' => $this->daysUntilDue,
            'deadline' => $this->workItem->deadline?->toDateString(),
            'message' => "Task {$this->workItem->ref_no} is due in {$this->daysUntilDue} day(s)",
        ];
    }
}
