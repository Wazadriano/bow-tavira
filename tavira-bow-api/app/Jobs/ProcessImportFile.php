<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WorkItem;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Risk;
use App\Notifications\ImportCompletedNotification;
use App\Services\ImportNormalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    public function __construct(
        string $filePath,
        string $type,
        array $columnMapping,
        int $userId
    ) {
        $this->filePath = $filePath;
        $this->type = $type;
        $this->columnMapping = $columnMapping;
        $this->userId = $userId;
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

        try {
            // Read file content
            $fullPath = Storage::path($this->filePath);
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

            if (in_array($extension, ['xlsx', 'xls'])) {
                $data = $this->parseExcel($fullPath);
            } else {
                $content = Storage::get($this->filePath);
                $data = $importService->parseCSV($content);
            }

            // Skip header row
            $rows = array_slice($data, 1);
            $results['total'] = count($rows);

            // Process in batches
            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                try {
                    $mappedData = $this->mapRowToFields($row);
                    $result = $this->processRow($mappedData, $importService);

                    if ($result === 'created') {
                        $results['created']++;
                    } elseif ($result === 'updated') {
                        $results['updated']++;
                    } else {
                        $results['skipped']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $results['skipped']++;

                    if (count($results['errors']) >= 100) {
                        $results['errors'][] = "Too many errors. Stopping at row " . ($index + 2);
                        break;
                    }
                }
            }

            DB::commit();

            // Cleanup temp file
            Storage::delete($this->filePath);

            // Notify user
            $this->notifyUser($results);

            Log::info("Import completed: " . json_encode($results));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Import failed: " . $e->getMessage());

            $results['errors'][] = "Import failed: " . $e->getMessage();
            $this->notifyUser($results, true);

            throw $e;
        }
    }

    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray(null, true, true, false);
    }

    private function mapRowToFields(array $row): array
    {
        $result = [];

        foreach ($this->columnMapping as $colIndex => $fieldName) {
            $result[$fieldName] = $row[$colIndex] ?? null;
        }

        return $result;
    }

    private function processRow(array $data, ImportNormalizationService $service): string
    {
        return match ($this->type) {
            'workitems' => $this->processWorkItem($data, $service),
            'suppliers' => $this->processSupplier($data, $service),
            'invoices' => $this->processInvoice($data, $service),
            'risks' => $this->processRisk($data, $service),
            default => 'skipped',
        };
    }

    private function processWorkItem(array $data, ImportNormalizationService $service): string
    {
        $refNo = $data['ref_no'] ?? null;

        if (empty($refNo)) {
            throw new \Exception("ref_no is required");
        }

        $existing = WorkItem::where('ref_no', $refNo)->first();

        $attributes = [
            'ref_no' => $refNo,
            'type' => $data['type'] ?? null,
            'activity' => $data['activity'] ?? null,
            'department' => $data['department'] ?? null,
            'description' => $data['description'] ?? null,
            'bau_or_transformative' => $data['bau_or_transformative'] ?? null,
            'impact_level' => $data['impact_level'] ?? null,
            'current_status' => $data['current_status'] ?? 'not_started',
            'deadline' => $service->transformValue($data['deadline'] ?? null, 'date'),
            'tags' => $this->parseTags($data['tags'] ?? null),
            'priority_item' => $service->transformValue($data['priority_item'] ?? false, 'bool'),
        ];

        // Handle responsible party lookup
        if (!empty($data['responsible_party_id'])) {
            $user = User::where('full_name', $data['responsible_party_id'])
                ->orWhere('email', $data['responsible_party_id'])
                ->first();
            if ($user) {
                $attributes['responsible_party_id'] = $user->id;
            }
        }

        if ($existing) {
            $existing->update($attributes);
            return 'updated';
        }

        WorkItem::create($attributes);
        return 'created';
    }

    private function processSupplier(array $data, ImportNormalizationService $service): string
    {
        $refNo = $data['ref_no'] ?? null;

        if (empty($refNo)) {
            throw new \Exception("ref_no is required");
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
            throw new \Exception("supplier_ref and invoice_ref are required");
        }

        $supplier = Supplier::where('ref_no', $supplierRef)->first();
        if (!$supplier) {
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
            'invoice_date' => $service->transformValue($data['invoice_date'] ?? null, 'date'),
            'due_date' => $service->transformValue($data['due_date'] ?? null, 'date'),
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
            throw new \Exception("ref_no is required");
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
        if (!empty($data['category_code'])) {
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

    private function parseTags(?string $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        return array_map('trim', explode(',', $tags));
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
