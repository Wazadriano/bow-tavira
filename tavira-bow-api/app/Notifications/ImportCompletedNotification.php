<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $importType;
    private array $results;
    private bool $failed;

    public function __construct(string $importType, array $results, bool $failed = false)
    {
        $this->importType = $importType;
        $this->results = $results;
        $this->failed = $failed;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->failed
                ? "Import {$this->importType} failed"
                : "Import {$this->importType} completed"
            );

        if ($this->failed) {
            $mail->error()
                ->line("Your import of {$this->importType} has failed.")
                ->line("Error: " . ($this->results['errors'][0] ?? 'Unknown error'));
        } else {
            $mail->success()
                ->line("Your import of {$this->importType} has completed successfully.")
                ->line("Total rows processed: {$this->results['total']}")
                ->line("Created: {$this->results['created']}")
                ->line("Updated: {$this->results['updated']}")
                ->line("Skipped: {$this->results['skipped']}");

            if (!empty($this->results['errors'])) {
                $mail->line("Errors encountered: " . count($this->results['errors']));
            }
        }

        return $mail->action('View Dashboard', url('/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'import_completed',
            'import_type' => $this->importType,
            'results' => $this->results,
            'failed' => $this->failed,
            'message' => $this->failed
                ? "Import {$this->importType} failed"
                : "Import {$this->importType} completed: {$this->results['created']} created, {$this->results['updated']} updated",
        ];
    }
}
