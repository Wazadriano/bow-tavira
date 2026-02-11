<?php

namespace App\Notifications;

use App\Models\Risk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiskThresholdBreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Risk $risk,
        private readonly string $appetiteStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = strtoupper($this->appetiteStatus);

        return (new MailMessage)
            ->subject("Risk {$this->risk->ref_no} appetite {$status}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("Risk **{$this->risk->ref_no} - {$this->risk->name}** has appetite status: **{$status}**.")
            ->line('Residual score: '.$this->risk->residual_risk_score)
            ->line('RAG: '.($this->risk->residual_rag?->value ?? 'N/A'))
            ->action('View Risk', url("/risks/{$this->risk->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'risk_threshold_breached',
            'risk_id' => $this->risk->id,
            'ref_no' => $this->risk->ref_no,
            'name' => $this->risk->name,
            'appetite_status' => $this->appetiteStatus,
            'residual_score' => $this->risk->residual_risk_score,
            'message' => "Risk {$this->risk->ref_no} appetite is {$this->appetiteStatus}",
        ];
    }
}
