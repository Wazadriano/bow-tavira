<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailySummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $summary
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $overdue = $this->summary['overdue_tasks'] ?? 0;
        $dueToday = $this->summary['due_today'] ?? 0;
        $highRisks = $this->summary['high_risks'] ?? 0;
        $expiringContracts = $this->summary['expiring_contracts'] ?? 0;

        return (new MailMessage)
            ->subject('BOW - Daily Summary')
            ->greeting('Good morning,')
            ->line("{$overdue} tasks overdue")
            ->line("{$dueToday} tasks due today")
            ->line("{$highRisks} high-risk items (RED)")
            ->line("{$expiringContracts} contracts expiring within 30 days")
            ->action('View Dashboard', config('app.frontend_url', 'http://localhost:3000').'/dashboard');
    }
}
