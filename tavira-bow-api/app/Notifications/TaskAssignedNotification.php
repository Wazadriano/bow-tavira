<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly WorkItem $workItem,
        private readonly User $assignedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You have been assigned to task {$this->workItem->ref_no}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("{$this->assignedBy->full_name} has assigned you to task **{$this->workItem->ref_no}**.")
            ->line('Description: '.$this->workItem->description)
            ->line('Deadline: '.($this->workItem->deadline?->toDateString() ?? 'No deadline'))
            ->action('View Task', url("/tasks/{$this->workItem->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'work_item_id' => $this->workItem->id,
            'ref_no' => $this->workItem->ref_no,
            'description' => $this->workItem->description,
            'assigned_by' => $this->assignedBy->full_name,
            'message' => "You have been assigned to task {$this->workItem->ref_no}",
        ];
    }
}
