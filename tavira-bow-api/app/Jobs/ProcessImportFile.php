<?php

namespace App\Jobs;

use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\ImportCompletedNotification;
use App\Services\ImportNormalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessImportFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    private string $filePath;

    private string $type;

    private array $columnMapping;

    private int $userId;

    private array|string|null $sheetNames;

    private ?string $jobId;

    public function __construct(
        string $filePath,
        string $type,
        array $columnMapping,
        int $userId,
        array|string|null $sheetNames = null,
        ?string $jobId = null
    ) {
        $this->filePath = $filePath;
        $this->type = $type;
        $this->columnMapping = $columnMapping;
        $this->userId = $userId;
        $this->sheetNames = $sheetNames;
        $this->jobId = $jobId;
    }

    public function handle(ImportNormalizationService $importService): void
    {
        Log::info("Processing import file: {$this->filePath} for type: {$this->type}");

        $results = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $this->updateProgress('processing', 0, 0, 'Starting import...');

        try {
            $fullPath = Storage::path($this->filePath);
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

            // Build list of sheet datasets to process
            $sheetDatasets = [];

            if (in_array($extension, ['xlsx', 'xls'])) {
                $sheetsToProcess = $this->resolveSheetNames();

                if ($sheetsToProcess) {
                    // Sort: "BOW List" first if present
                    usort($sheetsToProcess, function ($a, $b) {
                        if ($a === 'BOW List') {
                            return -1;
                        }
                        if ($b === 'BOW List') {
                            return 1;
                        }

                        return 0;
                    });

                    foreach ($sheetsToProcess as $sheetName) {
                        $data = $this->parseExcel($fullPath, $sheetName);
                        if (count($data) >= 2) {
                            $sheetDatasets[] = ['name' => $sheetName, 'data' => $data];
                        }
                    }
                } else {
                    $data = $this->parseExcel($fullPath);
                    if (count($data) >= 2) {
                        $sheetDatasets[] = ['name' => null, 'data' => $data];
                    }
                }
            } else {
                $content = Storage::get($this->filePath);
                $data = $importService->parseCSV($content);
                if (count($data) >= 2) {
                    $sheetDatasets[] = ['name' => null, 'data' => $data];
                }
            }

            // Count total rows across all sheets
            foreach ($sheetDatasets as $dataset) {
                $results['total'] += count($dataset['data']) - 1; // -1 for header
            }

            $this->updateProgress('processing', 0, $results['total'], 'Processing rows...');

            // Track processed ref_nos for dedup across sheets
            $processedRefNos = [];
            $globalRowIndex = 0;

            DB::beginTransaction();

            foreach ($sheetDatasets as $dataset) {
                $sheetName = $dataset['name'];
                $sheetData = $dataset['data'];
                $headers = $sheetData[0];
                $rows = array_slice($sheetData, 1);

                // Re-compute column mapping per sheet using service
                $expectedColumns = $importService->getExpectedColumns($this->type);
                $sheetColumnMapping = $importService->mapColumns($headers, $expectedColumns);

                // Merge with user-provided mapping for first sheet
                $activeMapping = ! empty($sheetColumnMapping) ? $sheetColumnMapping : $this->columnMapping;

                Log::info('Processing sheet: '.($sheetName ?? 'default')." ({$this->countDataRows($rows)} rows, ".count($activeMapping).' mapped columns)');

                foreach ($rows as $index => $row) {
                    $globalRowIndex++;
                    try {
                        $mappedData = $this->mapRowToFieldsWithMapping($row, $activeMapping);

                        // Dedup by ref_no across sheets
                        $refNo = $mappedData['ref_no'] ?? null;
                        if ($refNo !== null && trim((string) $refNo) !== '') {
                            $refNoKey = trim((string) $refNo);
                            if (isset($processedRefNos[$refNoKey])) {
                                $results['skipped']++;

                                continue;
                            }
                            $processedRefNos[$refNoKey] = true;
                        }

                        $result = $this->processRow($mappedData, $importService);

                        if ($result === 'created') {
                            $results['created']++;
                        } elseif ($result === 'updated') {
                            $results['updated']++;
                        } else {
                            $results['skipped']++;
                        }
                    } catch (\Exception $e) {
                        $sheetLabel = $sheetName ? "[{$sheetName}] " : '';
                        $results['errors'][] = $sheetLabel.'Row '.($index + 2).': '.$e->getMessage();
                        $results['skipped']++;

                        if (count($results['errors']) >= 100) {
                            $results['errors'][] = 'Too many errors. Stopping.';
                            break 2;
                        }
                    }

                    // Update progress every 10 rows
                    if ($globalRowIndex % 10 === 0) {
                        $processed = $results['created'] + $results['updated'] + $results['skipped'];
                        $this->updateProgress('processing', $processed, $results['total'], "Processed {$processed}/{$results['total']} rows");
                    }
                }
            }

            DB::commit();

            // Cleanup temp file
            Storage::delete($this->filePath);

            // Final progress
            $processed = $results['created'] + $results['updated'] + $results['skipped'];
            $this->updateProgress('completed', $processed, $results['total'], 'Import completed', $results);

            // Notify user
            $this->notifyUser($results);

            Log::info('Import completed: '.json_encode($results));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed: '.$e->getMessage());

            $results['errors'][] = 'Import failed: '.$e->getMessage();
            $this->updateProgress('failed', 0, 0, 'Import failed: '.$e->getMessage(), $results);
            $this->notifyUser($results, true);

            throw $e;
        }
    }

    private function resolveSheetNames(): ?array
    {
        if ($this->sheetNames === null) {
            return null;
        }

        if (is_string($this->sheetNames)) {
            return [$this->sheetNames];
        }

        return $this->sheetNames;
    }

    private function countDataRows(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if (! $this->isRowEmpty($row)) {
                $count++;
            }
        }

        return $count;
    }

    private function parseExcel(string $path, ?string $sheetName = null): array
    {
        $spreadsheet = IOFactory::load($path);

        if ($sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                $sheet = $spreadsheet->getActiveSheet();
            }
        } else {
            $sheet = $spreadsheet->getActiveSheet();
        }

        return $sheet->toArray(null, true, true, false);
    }

    private function mapRowToFields(array $row): array
    {
        return $this->mapRowToFieldsWithMapping($row, $this->columnMapping);
    }

    private function mapRowToFieldsWithMapping(array $row, array $mapping): array
    {
        $result = [];

        foreach ($mapping as $colIndex => $fieldName) {
            $result[$fieldName] = $row[$colIndex] ?? null;
        }

        return $result;
    }

    private function isRowEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function processRow(array $data, ImportNormalizationService $service): string
    {
        if ($this->isRowEmpty($data)) {
            return 'skipped';
        }

        return match ($this->type) {
            'workitems' => $this->processWorkItem($data, $service),
            'suppliers' => $this->processSupplier($data, $service),
            'invoices' => $this->processInvoice($data, $service),
            'risks' => $this->processRisk($data, $service),
            'governance' => $this->processGovernance($data, $service),
            default => 'skipped',
        };
    }

    private function processWorkItem(array $data, ImportNormalizationService $service): string
    {
        $refNo = $data['ref_no'] ?? null;

        if (empty($refNo)) {
            throw new \Exception('ref_no is required');
        }

        $existing = WorkItem::where('ref_no', $refNo)->first();

        $attributes = [
            'ref_no' => $refNo,
            'type' => $data['type'] ?? null,
            'activity' => $data['activity'] ?? null,
            'department' => $data['department'] ?? null,
            'description' => $data['description'] ?? null,
            'goal' => $data['goal'] ?? null,
            'bau_or_transformative' => $service->normalizeEnumValue($data['bau_or_transformative'] ?? null, BAUType::class),
            'impact_level' => $service->normalizeEnumValue($data['impact_level'] ?? null, ImpactLevel::class),
            'current_status' => $service->normalizeEnumValue($data['current_status'] ?? null, CurrentStatus::class) ?? 'Not Started',
            'rag_status' => $service->normalizeEnumValue($data['rag_status'] ?? null, RAGStatus::class),
            'deadline' => $service->parseDate($data['deadline'] ?? null),
            'completion_date' => $service->parseDate($data['completion_date'] ?? null),
            'monthly_update' => $data['monthly_update'] ?? null,
            'comments' => $data['comments'] ?? null,
            'update_frequency' => $service->normalizeEnumValue($data['update_frequency'] ?? null, UpdateFrequency::class),
            'tags' => $this->parseTags($data['tags'] ?? null),
            'priority_item' => $service->transformValue($data['priority_item'] ?? false, 'bool'),
            'cost_savings' => $service->transformValue($data['cost_savings'] ?? null, 'decimal'),
            'cost_efficiency_fte' => $service->transformValue($data['cost_efficiency_fte'] ?? null, 'decimal'),
            'expected_cost' => $service->transformValue($data['expected_cost'] ?? null, 'decimal'),
            'revenue_potential' => $service->transformValue($data['revenue_potential'] ?? null, 'decimal'),
        ];

        // Handle responsible party lookup
        if (! empty($data['responsible_party_id'])) {
            $attributes['responsible_party_id'] = $service->resolveUserId($data['responsible_party_id']);
        }

        // Handle department head lookup
        if (! empty($data['department_head_id'])) {
            $attributes['department_head_id'] = $service->resolveUserId($data['department_head_id']);
        }

        // Remove null values to avoid overwriting existing data with nulls on update
        $attributes = array_filter($attributes, fn ($v) => $v !== null);

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        // For create, ensure required fields
        $attributes['current_status'] = $attributes['current_status'] ?? 'Not Started';
        WorkItem::create($attributes);

        return 'created';
    }

    private function processSupplier(array $data, ImportNormalizationService $service): string
    {
        $refNo = $data['ref_no'] ?? null;

        if (empty($refNo)) {
            throw new \Exception('ref_no is required');
        }

        $existing = Supplier::where('ref_no', $refNo)->first();

        $attributes = [
            'ref_no' => $refNo,
            'name' => $data['name'] ?? null,
            'location' => $data['location'] ?? null,
            'is_common_provider' => $service->transformValue($data['is_common_provider'] ?? false, 'bool'),
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ];

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        Supplier::create($attributes);

        return 'created';
    }

    private function processInvoice(array $data, ImportNormalizationService $service): string
    {
        $supplierRef = $data['supplier_ref'] ?? null;
        $invoiceRef = $data['invoice_ref'] ?? null;

        if (empty($supplierRef) || empty($invoiceRef)) {
            throw new \Exception('supplier_ref and invoice_ref are required');
        }

        $supplier = Supplier::where('ref_no', $supplierRef)->first();
        if (! $supplier) {
            throw new \Exception("Supplier not found: {$supplierRef}");
        }

        $existing = SupplierInvoice::where('supplier_id', $supplier->id)
            ->where('invoice_ref', $invoiceRef)
            ->first();

        $attributes = [
            'supplier_id' => $supplier->id,
            'invoice_ref' => $invoiceRef,
            'description' => $data['description'] ?? null,
            'amount' => $service->transformValue($data['amount'] ?? 0, 'decimal'),
            'currency' => $data['currency'] ?? 'EUR',
            'invoice_date' => $service->parseDate($data['invoice_date'] ?? null),
            'due_date' => $service->parseDate($data['due_date'] ?? null),
            'frequency' => $data['frequency'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ];

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        SupplierInvoice::create($attributes);

        return 'created';
    }

    private function processRisk(array $data, ImportNormalizationService $service): string
    {
        $refNo = $data['ref_no'] ?? null;

        if (empty($refNo)) {
            throw new \Exception('ref_no is required');
        }

        $existing = Risk::where('ref_no', $refNo)->first();

        $attributes = [
            'ref_no' => $refNo,
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'tier' => $data['tier'] ?? null,
            'financial_impact' => $service->transformValue($data['financial_impact'] ?? 1, 'int'),
            'regulatory_impact' => $service->transformValue($data['regulatory_impact'] ?? 1, 'int'),
            'reputational_impact' => $service->transformValue($data['reputational_impact'] ?? 1, 'int'),
            'inherent_probability' => $service->transformValue($data['inherent_probability'] ?? 1, 'int'),
        ];

        // Handle category lookup
        if (! empty($data['category_code'])) {
            $category = \App\Models\RiskCategory::where('code', $data['category_code'])->first();
            if ($category) {
                $attributes['category_id'] = $category->id;
            }
        }

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        Risk::create($attributes);

        return 'created';
    }

    private function processGovernance(array $data, ImportNormalizationService $service): string
    {
        $refNo = $data['ref_no'] ?? null;

        if (empty($refNo)) {
            throw new \Exception('ref_no is required');
        }

        $existing = GovernanceItem::where('ref_no', $refNo)->first();

        $attributes = [
            'ref_no' => $refNo,
            'activity' => $data['activity'] ?? null,
            'description' => $data['description'] ?? null,
            'department' => $data['department'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'location' => $data['location'] ?? null,
            'current_status' => $data['current_status'] ?? 'not_started',
            'deadline' => $service->parseDate($data['deadline'] ?? null),
            'monthly_update' => $data['monthly_update'] ?? null,
            'tags' => $this->parseTags($data['tags'] ?? null),
        ];

        // Handle responsible party lookup
        if (! empty($data['responsible_party_id'])) {
            $attributes['responsible_party_id'] = $service->resolveUserId($data['responsible_party_id']);
        }

        $attributes = array_filter($attributes, fn ($v) => $v !== null);

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        $attributes['current_status'] = $attributes['current_status'] ?? 'not_started';
        GovernanceItem::create($attributes);

        return 'created';
    }

    private function parseTags(?string $tags): ?array
    {
        if (empty($tags)) {
            return null;
        }

        return array_map('trim', explode(',', $tags));
    }

    private function updateProgress(string $status, int $processed, int $total, string $message, ?array $results = null): void
    {
        if (! $this->jobId) {
            return;
        }

        $data = [
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'message' => $message,
            'percentage' => $total > 0 ? round(($processed / $total) * 100) : 0,
        ];

        if ($results) {
            $data['results'] = $results;
        }

        Cache::put("import_progress_{$this->jobId}", $data, now()->addHours(1));
    }

    private function notifyUser(array $results, bool $failed = false): void
    {
        $user = User::find($this->userId);

        if ($user) {
            $user->notify(new ImportCompletedNotification(
                $this->type,
                $results,
                $failed
            ));
        }
    }
}
