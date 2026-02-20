<?php

namespace Database\Seeders;

use App\Enums\InvoiceFrequency;
use App\Enums\InvoiceStatus;
use App\Enums\SupplierLocation;
use App\Enums\SupplierStatus;
use App\Models\ContractEntity;
use App\Models\Supplier;
use App\Models\SupplierAccess;
use App\Models\SupplierContract;
use App\Models\SupplierEntity;
use App\Models\SupplierInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RealisticSuppliersSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = $this->getSupplierData();
        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();

        foreach ($suppliers as $supplierData) {
            $contracts = $supplierData['contracts'];
            $invoices = $supplierData['invoices'];
            $entities = $supplierData['entities'];
            unset($supplierData['contracts'], $supplierData['invoices'], $supplierData['entities']);

            $supplier = Supplier::updateOrCreate(
                ['name' => $supplierData['name']],
                $supplierData
            );

            // Contracts
            foreach ($contracts as $contract) {
                $sc = SupplierContract::updateOrCreate(
                    ['supplier_id' => $supplier->id, 'contract_ref' => $contract['contract_ref']],
                    array_merge($contract, ['supplier_id' => $supplier->id])
                );

                // Contract entities
                foreach ($entities as $entity) {
                    ContractEntity::firstOrCreate(
                        ['contract_id' => $sc->id, 'entity' => $entity]
                    );
                }
            }

            // Invoices
            foreach ($invoices as $invoice) {
                $invoiceNumber = $invoice['invoice_ref'];
                unset($invoice['invoice_ref'], $invoice['_paid_date']);
                SupplierInvoice::updateOrCreate(
                    ['supplier_id' => $supplier->id, 'invoice_number' => $invoiceNumber],
                    array_merge($invoice, ['supplier_id' => $supplier->id])
                );
            }

            // Entities
            foreach ($entities as $entity) {
                SupplierEntity::firstOrCreate(
                    ['supplier_id' => $supplier->id, 'entity' => $entity]
                );
            }

            // Admin access
            foreach ($adminIds as $adminId) {
                SupplierAccess::firstOrCreate(
                    ['supplier_id' => $supplier->id, 'user_id' => $adminId],
                    ['can_view' => true, 'can_edit' => true]
                );
            }
        }

        $this->command->info('Suppliers: '.Supplier::count().' total, '.SupplierContract::count().' contracts, '.SupplierInvoice::count().' invoices');
    }

    private function getSupplierData(): array
    {
        return [
            $this->bloomberg(),
            $this->refinitiv(),
            $this->deloitte(),
            $this->kpmg(),
            $this->pershing(),
            $this->interactiveBrokers(),
            $this->ice(),
            $this->ssc(),
            $this->broadridge(),
            $this->azure(),
            $this->macfarlanes(),
            $this->aon(),
            $this->robertHalf(),
            $this->registr(),
            $this->sage(),
        ];
    }

    private function bloomberg(): array
    {
        return [
            'name' => 'Bloomberg LP',
            'contact_name' => 'David Chen',
            'contact_email' => 'dchen@bloomberg.net',
            'contact_phone' => '+44 20 7330 7500',
            'address' => '3 Queen Victoria Street, London EC4N 4TQ',
            'category' => 'Data Services',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => true,
            'notes' => 'Primary market data provider. Terminal and Data License subscriptions.',
            'contracts' => [
                ['contract_ref' => 'CTR-BLM-001', 'description' => 'Bloomberg Terminal - 8 seats', 'start_date' => '2024-04-01', 'end_date' => '2027-03-31', 'amount' => 192000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 90, 'notes' => '24,000 GBP per seat per year'],
                ['contract_ref' => 'CTR-BLM-002', 'description' => 'Bloomberg Data License - Enterprise', 'start_date' => '2025-01-01', 'end_date' => '2026-12-31', 'amount' => 85000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'Pricing data feed for portfolio management'],
            ],
            'invoices' => $this->generateMonthlyInvoices('BLM', 16000.00, 'GBP', 12),
            'entities' => ['UK', 'Global'],
        ];
    }

    private function refinitiv(): array
    {
        return [
            'name' => 'Refinitiv (LSEG)',
            'contact_name' => 'Sarah Phillips',
            'contact_email' => 'sarah.phillips@lseg.com',
            'contact_phone' => '+44 20 7250 1122',
            'address' => '5 Canada Square, Canary Wharf, London E14 5AQ',
            'category' => 'Data Services',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => true,
            'notes' => 'Eikon platform for research and analytics.',
            'contracts' => [
                ['contract_ref' => 'CTR-REF-001', 'description' => 'Eikon Desktop - 5 seats', 'start_date' => '2025-03-01', 'end_date' => '2027-02-28', 'amount' => 72000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 90, 'notes' => '14,400 GBP per seat per year'],
            ],
            'invoices' => $this->generateQuarterlyInvoices('REF', 18000.00, 'GBP', 4),
            'entities' => ['UK'],
        ];
    }

    private function deloitte(): array
    {
        return [
            'name' => 'Deloitte LLP',
            'contact_name' => 'James Richardson',
            'contact_email' => 'jrichardson@deloitte.co.uk',
            'contact_phone' => '+44 20 7936 3000',
            'address' => '1 New Street Square, London EC4A 3HQ',
            'category' => 'Consulting',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'External audit, tax advisory, and consulting services.',
            'contracts' => [
                ['contract_ref' => 'CTR-DEL-001', 'description' => 'External Audit Services FY2025-2027', 'start_date' => '2025-01-01', 'end_date' => '2027-12-31', 'amount' => 145000.00, 'currency' => 'GBP', 'auto_renewal' => false, 'notice_period_days' => 90, 'notes' => 'Statutory audit for all UK entities'],
                ['contract_ref' => 'CTR-DEL-002', 'description' => 'Tax Advisory Retainer', 'start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'amount' => 42000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'Cross-border tax planning and compliance'],
                ['contract_ref' => 'CTR-DEL-003', 'description' => 'Regulatory Consulting - FCA', 'start_date' => '2025-09-01', 'end_date' => '2026-08-31', 'amount' => 65000.00, 'currency' => 'GBP', 'auto_renewal' => false, 'notice_period_days' => 30, 'notes' => 'SMCR compliance review and advisory'],
            ],
            'invoices' => [
                ...$this->generateQuarterlyInvoices('DEL-AUD', 36250.00, 'GBP', 4),
                ['invoice_ref' => 'INV-DEL-TAX-001', 'description' => 'Tax advisory H1 2025', 'amount' => 21000.00, 'currency' => 'GBP', 'invoice_date' => '2025-10-01', 'due_date' => '2025-10-31', '_paid_date' => '2025-10-28', 'frequency' => InvoiceFrequency::ANNUALLY, 'status' => InvoiceStatus::PAID],
                ['invoice_ref' => 'INV-DEL-REG-001', 'description' => 'Regulatory consulting phase 1', 'amount' => 32500.00, 'currency' => 'GBP', 'invoice_date' => '2026-01-15', 'due_date' => '2026-02-15', 'frequency' => InvoiceFrequency::QUARTERLY, 'status' => InvoiceStatus::PENDING],
            ],
            'entities' => ['UK', 'Global'],
        ];
    }

    private function kpmg(): array
    {
        return [
            'name' => 'KPMG',
            'contact_name' => 'Emily Watson',
            'contact_email' => 'ewatson@kpmg.co.uk',
            'contact_phone' => '+44 20 7311 1000',
            'address' => '15 Canada Square, Canary Wharf, London E14 5GL',
            'category' => 'Financial Services',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Tax advisory services for international structures.',
            'contracts' => [
                ['contract_ref' => 'CTR-KPM-001', 'description' => 'International Tax Advisory', 'start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'amount' => 55000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'Transfer pricing and substance requirements'],
            ],
            'invoices' => $this->generateQuarterlyInvoices('KPM', 13750.00, 'GBP', 4),
            'entities' => ['UK', 'Dubai', 'Monaco'],
        ];
    }

    private function pershing(): array
    {
        return [
            'name' => 'Pershing (BNY Mellon)',
            'contact_name' => 'Michael Torres',
            'contact_email' => 'michael.torres@pershing.com',
            'contact_phone' => '+44 20 7163 3000',
            'address' => 'One Canada Square, Canary Wharf, London E14 5AL',
            'category' => 'Financial Services',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => true,
            'notes' => 'Custody and clearing services for client assets.',
            'contracts' => [
                ['contract_ref' => 'CTR-PER-001', 'description' => 'Custody Services Agreement', 'start_date' => '2023-07-01', 'end_date' => '2026-06-30', 'amount' => 280000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 180, 'notes' => 'Primary custodian for UK and international assets'],
                ['contract_ref' => 'CTR-PER-002', 'description' => 'Clearing & Settlement', 'start_date' => '2023-07-01', 'end_date' => '2026-06-30', 'amount' => 95000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 180, 'notes' => 'Trade clearing and settlement services'],
            ],
            'invoices' => $this->generateMonthlyInvoices('PER', 31250.00, 'GBP', 12),
            'entities' => ['UK', 'Global'],
        ];
    }

    private function interactiveBrokers(): array
    {
        return [
            'name' => 'Interactive Brokers',
            'contact_name' => 'Alex Morrison',
            'contact_email' => 'amorrison@interactivebrokers.com',
            'contact_phone' => '+44 20 7710 5600',
            'address' => '20 Fenchurch Street, London EC3M 3BY',
            'category' => 'Financial Services',
            'location' => SupplierLocation::GLOBAL,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Execution and prime brokerage services.',
            'contracts' => [
                ['contract_ref' => 'CTR-IB-001', 'description' => 'Prime Brokerage Agreement', 'start_date' => '2024-01-01', 'end_date' => '2026-12-31', 'amount' => 48000.00, 'currency' => 'USD', 'auto_renewal' => true, 'notice_period_days' => 90, 'notes' => 'Commission-based with minimum monthly fees'],
            ],
            'invoices' => $this->generateMonthlyInvoices('IB', 4000.00, 'USD', 12),
            'entities' => ['UK', 'Global'],
        ];
    }

    private function ice(): array
    {
        return [
            'name' => 'ICE (Intercontinental Exchange)',
            'contact_name' => 'Robert Harding',
            'contact_email' => 'rharding@ice.com',
            'contact_phone' => '+44 20 7065 7700',
            'address' => '5 Milton Gate, London EC2Y 9DA',
            'category' => 'Technology',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Trading platform and market connectivity.',
            'contracts' => [
                ['contract_ref' => 'CTR-ICE-001', 'description' => 'ICE Connect Platform License', 'start_date' => '2025-06-01', 'end_date' => '2027-05-31', 'amount' => 36000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'Fixed income trading platform access'],
            ],
            'invoices' => $this->generateQuarterlyInvoices('ICE', 9000.00, 'GBP', 4),
            'entities' => ['UK'],
        ];
    }

    private function ssc(): array
    {
        return [
            'name' => 'SS&C Technologies',
            'contact_name' => 'Karen Liu',
            'contact_email' => 'kliu@sscinc.com',
            'contact_phone' => '+44 20 7397 5500',
            'address' => 'Tower 42, 25 Old Broad Street, London EC2N 1HQ',
            'category' => 'Technology',
            'location' => SupplierLocation::GLOBAL,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Fund administration and portfolio management systems.',
            'contracts' => [
                ['contract_ref' => 'CTR-SSC-001', 'description' => 'Advent Geneva - Portfolio Accounting', 'start_date' => '2024-06-01', 'end_date' => '2027-05-31', 'amount' => 62000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 90, 'notes' => 'Core portfolio management system'],
                ['contract_ref' => 'CTR-SSC-002', 'description' => 'Black Diamond - Client Reporting', 'start_date' => '2025-01-01', 'end_date' => '2026-12-31', 'amount' => 28000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'Client-facing wealth reporting portal'],
            ],
            'invoices' => $this->generateMonthlyInvoices('SSC', 7500.00, 'GBP', 12),
            'entities' => ['UK', 'Singapore', 'Global'],
        ];
    }

    private function broadridge(): array
    {
        return [
            'name' => 'Broadridge Financial Solutions',
            'contact_name' => 'Victoria Spencer',
            'contact_email' => 'victoria.spencer@broadridge.com',
            'contact_phone' => '+44 20 7551 3000',
            'address' => '70 Gracechurch Street, London EC3V 0HR',
            'category' => 'Technology',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Regulatory reporting and investor communications.',
            'contracts' => [
                ['contract_ref' => 'CTR-BRD-001', 'description' => 'Regulatory Reporting Platform', 'start_date' => '2025-03-01', 'end_date' => '2027-02-28', 'amount' => 44000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'MiFID II, EMIR, and UK reporting'],
            ],
            'invoices' => $this->generateQuarterlyInvoices('BRD', 11000.00, 'GBP', 4),
            'entities' => ['UK'],
        ];
    }

    private function azure(): array
    {
        return [
            'name' => 'Microsoft Azure',
            'contact_name' => 'Azure Enterprise Support',
            'contact_email' => 'enterprise@microsoft.com',
            'contact_phone' => '+44 344 800 2400',
            'address' => '2 Kingdom Street, Paddington, London W2 6BD',
            'category' => 'Technology',
            'location' => SupplierLocation::GLOBAL,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => true,
            'notes' => 'Cloud infrastructure hosting all production workloads.',
            'contracts' => [
                ['contract_ref' => 'CTR-AZR-001', 'description' => 'Azure Enterprise Agreement', 'start_date' => '2025-01-01', 'end_date' => '2027-12-31', 'amount' => 96000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 90, 'notes' => 'Reserved instances + pay-as-you-go hybrid'],
            ],
            'invoices' => $this->generateMonthlyInvoices('AZR', 8000.00, 'GBP', 12),
            'entities' => ['UK', 'Global'],
        ];
    }

    private function macfarlanes(): array
    {
        return [
            'name' => 'Macfarlanes LLP',
            'contact_name' => 'Helen Wright',
            'contact_email' => 'helen.wright@macfarlanes.com',
            'contact_phone' => '+44 20 7831 9222',
            'address' => '20 Cursitor Street, London EC4A 1LT',
            'category' => 'Legal',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'External legal counsel for regulatory and corporate matters.',
            'contracts' => [
                ['contract_ref' => 'CTR-MAC-001', 'description' => 'Legal Services Retainer', 'start_date' => '2025-04-01', 'end_date' => '2026-03-31', 'amount' => 75000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 30, 'notes' => 'Retainer with ad-hoc billing above threshold'],
            ],
            'invoices' => $this->generateQuarterlyInvoices('MAC', 18750.00, 'GBP', 4),
            'entities' => ['UK'],
        ];
    }

    private function aon(): array
    {
        return [
            'name' => 'Aon plc',
            'contact_name' => 'Thomas Marshall',
            'contact_email' => 'thomas.marshall@aon.com',
            'contact_phone' => '+44 20 7623 5500',
            'address' => 'The Leadenhall Building, 122 Leadenhall Street, London EC3V 4AN',
            'category' => 'Financial Services',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Professional indemnity and directors & officers insurance.',
            'contracts' => [
                ['contract_ref' => 'CTR-AON-001', 'description' => 'Professional Indemnity Insurance', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'amount' => 125000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'PI cover 10M GBP limit'],
                ['contract_ref' => 'CTR-AON-002', 'description' => 'Directors & Officers Insurance', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'amount' => 45000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => 'D&O cover 5M GBP limit'],
            ],
            'invoices' => [
                ['invoice_ref' => 'INV-AON-001', 'description' => 'PI Insurance premium 2025-2026', 'amount' => 125000.00, 'currency' => 'GBP', 'invoice_date' => '2025-07-01', 'due_date' => '2025-07-31', '_paid_date' => '2025-07-15', 'frequency' => InvoiceFrequency::ANNUALLY, 'status' => InvoiceStatus::PAID],
                ['invoice_ref' => 'INV-AON-002', 'description' => 'D&O Insurance premium 2025-2026', 'amount' => 45000.00, 'currency' => 'GBP', 'invoice_date' => '2025-07-01', 'due_date' => '2025-07-31', '_paid_date' => '2025-07-15', 'frequency' => InvoiceFrequency::ANNUALLY, 'status' => InvoiceStatus::PAID],
            ],
            'entities' => ['UK', 'Global'],
        ];
    }

    private function robertHalf(): array
    {
        return [
            'name' => 'Robert Half International',
            'contact_name' => 'Amy Clark',
            'contact_email' => 'amy.clark@roberthalf.com',
            'contact_phone' => '+44 20 7936 2600',
            'address' => '30 Finsbury Square, London EC2A 1AG',
            'category' => 'Human Resources',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Specialist financial services recruitment.',
            'contracts' => [
                ['contract_ref' => 'CTR-RH-001', 'description' => 'Recruitment Services PSA', 'start_date' => '2025-01-01', 'end_date' => '2026-12-31', 'amount' => 0.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 30, 'notes' => 'Success-fee based, 20% of first year salary'],
            ],
            'invoices' => [
                ['invoice_ref' => 'INV-RH-001', 'description' => 'Placement fee - Compliance Officer', 'amount' => 18000.00, 'currency' => 'GBP', 'invoice_date' => '2025-08-15', 'due_date' => '2025-09-15', '_paid_date' => '2025-09-10', 'frequency' => InvoiceFrequency::AS_NEEDED, 'status' => InvoiceStatus::PAID],
                ['invoice_ref' => 'INV-RH-002', 'description' => 'Placement fee - Operations Analyst', 'amount' => 14000.00, 'currency' => 'GBP', 'invoice_date' => '2025-11-01', 'due_date' => '2025-12-01', '_paid_date' => '2025-11-28', 'frequency' => InvoiceFrequency::AS_NEEDED, 'status' => InvoiceStatus::PAID],
            ],
            'entities' => ['UK'],
        ];
    }

    private function registr(): array
    {
        return [
            'name' => 'Regis-TR',
            'contact_name' => 'Christine Bauer',
            'contact_email' => 'cbauer@regis-tr.com',
            'contact_phone' => '+352 2477 4411',
            'address' => '42 Avenue JF Kennedy, L-1855 Luxembourg',
            'category' => 'Financial Services',
            'location' => SupplierLocation::GLOBAL,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Trade repository for EMIR regulatory reporting.',
            'contracts' => [
                ['contract_ref' => 'CTR-RTR-001', 'description' => 'EMIR Trade Reporting Service', 'start_date' => '2025-01-01', 'end_date' => '2026-12-31', 'amount' => 18000.00, 'currency' => 'EUR', 'auto_renewal' => true, 'notice_period_days' => 90, 'notes' => 'Mandatory EMIR derivative trade reporting'],
            ],
            'invoices' => $this->generateQuarterlyInvoices('RTR', 4500.00, 'EUR', 4),
            'entities' => ['UK', 'Global'],
        ];
    }

    private function sage(): array
    {
        return [
            'name' => 'Sage (UK) Ltd',
            'contact_name' => 'Paul Henderson',
            'contact_email' => 'phenderson@sage.com',
            'contact_phone' => '+44 191 294 3000',
            'address' => 'North Park, Newcastle upon Tyne NE13 9AA',
            'category' => 'Technology',
            'location' => SupplierLocation::LONDON,
            'status' => SupplierStatus::ACTIVE,
            'is_common_provider' => false,
            'notes' => 'Accounting software for general ledger and management accounts.',
            'contracts' => [
                ['contract_ref' => 'CTR-SAG-001', 'description' => 'Sage Intacct - Cloud Accounting', 'start_date' => '2025-04-01', 'end_date' => '2027-03-31', 'amount' => 24000.00, 'currency' => 'GBP', 'auto_renewal' => true, 'notice_period_days' => 60, 'notes' => '10 user licenses, multi-entity'],
            ],
            'invoices' => $this->generateMonthlyInvoices('SAG', 2000.00, 'GBP', 12),
            'entities' => ['UK'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateMonthlyInvoices(string $prefix, float $amount, string $currency, int $count): array
    {
        $invoices = [];
        $statuses = [InvoiceStatus::PAID, InvoiceStatus::PAID, InvoiceStatus::PAID, InvoiceStatus::APPROVED, InvoiceStatus::PENDING];

        for ($i = 0; $i < $count; $i++) {
            $date = Carbon::create(2025, 3 + $i, 1);
            if ($date->isAfter(now())) {
                break;
            }

            $status = $statuses[min($i, count($statuses) - 1)];
            $invoices[] = [
                'invoice_ref' => sprintf('INV-%s-%03d', $prefix, $i + 1),
                'description' => 'Service fee '.$date->format('M Y'),
                'amount' => $amount,
                'currency' => $currency,
                'invoice_date' => $date->format('Y-m-d'),
                'due_date' => $date->copy()->addDays(30)->format('Y-m-d'),
                '_paid_date' => $status === InvoiceStatus::PAID ? $date->copy()->addDays(rand(15, 28))->format('Y-m-d') : null,
                'frequency' => InvoiceFrequency::MONTHLY,
                'status' => $status,
            ];
        }

        return $invoices;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateQuarterlyInvoices(string $prefix, float $amount, string $currency, int $count): array
    {
        $invoices = [];
        $quarters = [
            ['2025-04-01', 'Q2 2025'], ['2025-07-01', 'Q3 2025'],
            ['2025-10-01', 'Q4 2025'], ['2026-01-01', 'Q1 2026'],
        ];

        for ($i = 0; $i < min($count, count($quarters)); $i++) {
            $date = Carbon::parse($quarters[$i][0]);
            $isPast = $date->isBefore(now());

            $invoices[] = [
                'invoice_ref' => sprintf('INV-%s-%03d', $prefix, $i + 1),
                'description' => 'Service fee '.$quarters[$i][1],
                'amount' => $amount,
                'currency' => $currency,
                'invoice_date' => $date->format('Y-m-d'),
                'due_date' => $date->copy()->addDays(30)->format('Y-m-d'),
                '_paid_date' => $isPast ? $date->copy()->addDays(rand(15, 25))->format('Y-m-d') : null,
                'frequency' => InvoiceFrequency::QUARTERLY,
                'status' => $isPast ? InvoiceStatus::PAID : InvoiceStatus::PENDING,
            ];
        }

        return $invoices;
    }
}
