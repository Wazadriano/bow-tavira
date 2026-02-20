<?php

namespace App\Notifications;

use App\Models\WorkItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly WorkItem $workItem,
        private readonly int $daysOverdue
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $responsibleName = $this->workItem->responsibleParty?->full_name ?? 'Unassigned';
        $deadlineStr = $this->workItem->deadline?->toDateString() ?? 'N/A';
        $statusStr = $this->workItem->current_status?->value ?? 'N/A';

        return (new MailMessage)
            ->subject("OVERDUE: Task {$this->workItem->ref_no} is {$this->daysOverdue} day(s) past deadline")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("Task **{$this->workItem->ref_no}** has not been completed by its deadline.")
            ->line("Description: {$this->workItem->description}")
            ->line("Department: {$this->workItem->department}")
            ->line("Deadline: {$deadlineStr}")
            ->line("Current status: {$statusStr}")
            ->line("Responsible party: {$responsibleName}")
            ->line("Days overdue: {$this->daysOverdue}")
            ->line('Please follow up with the responsible party.')
            ->action('View Task', url("/tasks/{$this->workItem->id}"));
    }

    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_overdue',
            'work_item_id' => $this->workItem->id,
            'ref_no' => $this->workItem->ref_no,
            'description' => $this->workItem->description,
            'days_overdue' => $this->daysOverdue,
            'deadline' => $this->workItem->deadline?->toDateString(),
            'message' => "Task {$this->workItem->ref_no} is {$this->daysOverdue} day(s) overdue",
        ];
    }
}
