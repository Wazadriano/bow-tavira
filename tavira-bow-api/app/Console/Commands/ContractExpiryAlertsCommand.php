<?php

namespace App\Console\Commands;

use App\Models\SupplierContract;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use Illuminate\Console\Command;

class ContractExpiryAlertsCommand extends Command
{
    protected $signature = 'bow:send-contract-alerts {--days=90 : Number of days to look ahead for expiring contracts}';

    protected $description = 'Send contract expiration alerts (RG-BOW-007) - contracts expiring within 90 days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $contracts = SupplierContract::query()
            ->with('supplier')
            ->expiringSoon($days)
            ->orderBy('end_date')
            ->get();

        if ($contracts->isEmpty()) {
            $this->info('No contracts expiring in the next '.$days.' days.');

            return Command::SUCCESS;
        }

        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        $totalSent = 0;

        foreach ($contracts as $contract) {
            $daysUntilExpiry = $contract->days_until_expiry ?? $days;

            foreach ($admins as $admin) {
                $admin->notify(new ContractExpiringNotification($contract, $daysUntilExpiry));
                $totalSent++;
            }

            $supplierName = $contract->supplier?->name ?? 'N/A';
            $this->line("  - [{$contract->contract_ref}] {$supplierName} - expires in {$daysUntilExpiry} days (".$contract->end_date?->toDateString().')');
        }

        $this->info("Contract alerts sent: {$totalSent} notification(s) for {$contracts->count()} contract(s).");

        return Command::SUCCESS;
    }
}
