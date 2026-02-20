<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkItem;
use App\Services\ICalendarService;
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

    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $deadlineStr = $this->workItem->deadline !== null ? $this->workItem->deadline->toDateString() : 'No deadline';

        $mail = (new MailMessage)
            ->subject("You have been assigned to task {$this->workItem->ref_no}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("{$this->assignedBy->full_name} has assigned you to task **{$this->workItem->ref_no}**.")
            ->line('Description: '.$this->workItem->description)
            ->line('Deadline: '.$deadlineStr)
            ->line('Please acknowledge this assignment on the platform.')
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
            'type' => 'task_assigned',
            'work_item_id' => $this->workItem->id,
            'ref_no' => $this->workItem->ref_no,
            'description' => $this->workItem->description,
            'assigned_by' => $this->assignedBy->full_name,
            'message' => "You have been assigned to task {$this->workItem->ref_no}",
        ];
    }
}
