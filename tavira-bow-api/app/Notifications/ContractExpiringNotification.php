<?php

namespace App\Notifications;

use App\Models\SupplierContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SupplierContract $contract,
        private readonly int $daysUntilExpiry
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
        $supplierName = $this->contract->supplier?->name ?? 'Unknown';
        $endDate = $this->contract->end_date !== null ? $this->contract->end_date->toDateString() : 'N/A';

        return (new MailMessage)
            ->subject("Contract {$this->contract->contract_ref} expiring in {$this->daysUntilExpiry} days")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("Contract **{$this->contract->contract_ref}** with **{$supplierName}** expires in {$this->daysUntilExpiry} days.")
            ->line('End date: '.$endDate)
            ->line('Contract value: '.number_format($this->contract->amount ?? 0, 2).' GBP')
            ->action('View Supplier', url("/suppliers/{$this->contract->supplier_id}"));
    }

    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contract_expiring',
            'contract_id' => $this->contract->id,
            'contract_ref' => $this->contract->contract_ref,
            'supplier_id' => $this->contract->supplier_id,
            'supplier_name' => $this->contract->supplier?->name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'end_date' => $this->contract->end_date !== null ? $this->contract->end_date->toDateString() : null,
            'message' => "Contract {$this->contract->contract_ref} expires in {$this->daysUntilExpiry} days",
        ];
    }
}
