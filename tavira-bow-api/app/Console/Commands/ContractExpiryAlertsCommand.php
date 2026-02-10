<?php

namespace App\Console\Commands;

use App\Models\SupplierContract;
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

        $this->info('Contract expiration alerts ('.$contracts->count().' contract(s) expiring within '.$days.' days):');
        foreach ($contracts as $contract) {
            $supplierName = $contract->supplier?->name ?? 'N/A';
            $this->line("  - [{$contract->contract_ref}] {$supplierName} - expires in {$contract->days_until_expiry} days (".$contract->end_date?->toDateString().')');
        }

        // Optional: dispatch notifications (email, in-app) when notification system exists
        // event(new ContractExpiringSoonEvent($contracts));

        return Command::SUCCESS;
    }
}
