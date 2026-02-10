<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Import\ConfirmImportRequest;
use App\Http\Requests\Import\PreviewImportRequest;
use App\Jobs\ProcessImportFile;
use App\Services\ImportNormalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportExportController extends Controller
{
    private ImportNormalizationService $importService;

    public function __construct(ImportNormalizationService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Preview import file
     */
    public function preview(PreviewImportRequest $request): JsonResponse
    {

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $sheets = [];
        $sheetInfo = [];
        $selectedSheet = $request->sheet_name;

        // Parse file content
        if (in_array($extension, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheets = $spreadsheet->getSheetNames();

            // Build sheet_info for each sheet
            foreach ($sheets as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if ($sheet) {
                    $highestCol = $sheet->getHighestDataColumn();
                    $highestRow = $sheet->getHighestDataRow();
                    $colCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
                    $rowCount = max(0, $highestRow - 1); // exclude header row
                    $sheetInfo[] = [
                        'name' => $sheetName,
                        'columns' => $colCount,
                        'rows' => $rowCount,
                        'importable' => $colCount >= 13,
                    ];
                }
            }

            // Auto-select "BOW List" if present and no sheet specified
            if (! $selectedSheet) {
                $bowListIndex = array_search('BOW List', $sheets);
                $selectedSheet = $bowListIndex !== false ? 'BOW List' : $sheets[0];
            }

            $data = $this->parseExcel($file->getPathname(), $selectedSheet);
        } else {
            $content = file_get_contents($file->getPathname());
            $data = $this->importService->parseCSV($content);
        }

        if (count($data) < 2) {
            return response()->json([
                'message' => 'File must contain at least one data row',
            ], 422);
        }

        // Get headers (first row) and preview data
        $headers = $data[0];
        $previewRows = array_slice($data, 1, 10);

        // Get expected columns for this type
        $expectedColumns = $this->importService->getExpectedColumns($request->type);

        // Auto-map columns
        $columnMapping = $this->importService->mapColumns($headers, $expectedColumns);

        // Validate preview rows
        $rules = $this->getValidationRules($request->type);
        $validationErrors = [];

        foreach ($previewRows as $index => $row) {
            $mappedRow = $this->mapRowToFields($row, $columnMapping);
            $errors = $this->importService->validateRow($mappedRow, $rules, $index + 2);
            if ($errors) {
                $validationErrors = array_merge($validationErrors, $errors);
            }
        }

        // Store file temporarily
        $tempPath = $file->store('imports/temp');

        return response()->json([
            'temp_file' => $tempPath,
            'sheets' => $sheets,
            'sheet_info' => $sheetInfo,
            'selected_sheet' => $selectedSheet,
            'headers' => $headers,
            'column_mapping' => $columnMapping,
            'expected_columns' => array_keys($expectedColumns),
            'preview_rows' => $previewRows,
            'total_rows' => count($data) - 1,
            'validation_errors' => array_slice($validationErrors, 0, 20),
            'warnings' => $this->importService->getWarnings(),
        ]);
    }

    /**
     * Confirm and execute import
     */
    public function confirm(ConfirmImportRequest $request): JsonResponse
    {
        $tempFile = $request->temp_file;

        if (! Storage::exists($tempFile)) {
            return response()->json([
                'message' => 'Temporary file not found. Please upload again.',
            ], 404);
        }

        $jobId = uniqid('import_', true);

        // Determine sheet(s) to import: sheet_names (array) takes priority over sheet_name (string)
        $sheetNames = $request->input('sheet_names');
        if (! $sheetNames) {
            $sheetName = $request->input('sheet_name');
            $sheetNames = $sheetName ? [$sheetName] : null;
        }

        // Queue the import job
        ProcessImportFile::dispatch(
            $tempFile,
            $request->type,
            $request->column_mapping,
            $request->user()->id,
            $sheetNames,
            $jobId
        );

        return response()->json([
            'message' => 'Import queued successfully. You will be notified when complete.',
            'job_id' => $jobId,
            'job_status' => 'queued',
        ], 202);
    }

    /**
     * Get import job status
     */
    public function status(string $jobId): JsonResponse
    {
        $progress = Cache::get("import_progress_{$jobId}");

        if (! $progress) {
            return response()->json([
                'status' => 'unknown',
                'message' => 'Job not found or not yet started.',
            ]);
        }

        return response()->json($progress);
    }

    /**
     * Download import template
     */
    public function template(string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $templates = [
            'workitems' => [
                'ref_no', 'type', 'activity', 'department', 'description', 'goal',
                'bau_or_transformative', 'impact_level', 'current_status',
                'deadline', 'completion_date', 'monthly_update', 'comments',
                'update_frequency', 'responsible_party', 'department_head',
                'tags', 'priority_item', 'cost_savings', 'cost_efficiency_fte',
                'expected_cost', 'revenue_potential',
            ],
            'suppliers' => [
                'ref_no', 'name', 'sage_category', 'location',
                'is_common_provider', 'status', 'entities', 'notes',
            ],
            'invoices' => [
                'supplier_ref', 'invoice_ref', 'description', 'amount',
                'currency', 'invoice_date', 'due_date', 'frequency', 'status',
            ],
            'risks' => [
                'ref_no', 'theme_code', 'category_code', 'name', 'description',
                'tier', 'owner', 'responsible_party', 'financial_impact',
                'regulatory_impact', 'reputational_impact', 'inherent_probability',
            ],
            'governance' => [
                'ref_no', 'activity', 'description', 'department', 'frequency',
                'location', 'responsible_party', 'deadline', 'tags',
            ],
        ];

        if (! isset($templates[$type])) {
            abort(404, 'Template not found');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        foreach ($templates[$type] as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Style headers
        $lastCol = chr(65 + min(count($templates[$type]) - 1, 25));
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create temp file
        $filename = "{$type}_template.xlsx";
        $tempPath = storage_path("app/temp/{$filename}");

        if (! file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend();
    }

    /**
     * Export work items
     */
    public function exportWorkItems(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->export($request, 'workitems');
    }

    /**
     * Export governance items
     */
    public function exportGovernance(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->export($request, 'governance');
    }

    /**
     * Export suppliers
     */
    public function exportSuppliers(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->export($request, 'suppliers');
    }

    /**
     * Export risks
     */
    public function exportRisks(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->export($request, 'risks');
    }

    /**
     * Export invoices
     */
    public function exportInvoices(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return $this->export($request, 'invoices');
    }

    /**
     * Export data
     */
    private function export(Request $request, string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $this->getExportData($type, $request);

        if (empty($data)) {
            abort(404, 'No data to export');
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        $headers = array_keys($data[0]);
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Add data
        foreach ($data as $rowNum => $row) {
            foreach (array_values($row) as $col => $value) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowNum + 2, $value);
            }
        }

        // Style headers
        $lastCol = chr(65 + min(count($headers) - 1, 25));
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create file
        $filename = "{$type}_export_".date('Y-m-d_His').'.xlsx';
        $tempPath = storage_path("app/temp/{$filename}");

        if (! file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend();
    }

    /**
     * Parse Excel file with optional sheet name selection
     */
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

    /**
     * Get validation rules for import type
     */
    private function getValidationRules(string $type): array
    {
        return match ($type) {
            'workitems' => [
                'ref_no' => 'required|string',
                'department' => 'required|string',
                'description' => 'required|string',
                'deadline' => 'date',
            ],
            'suppliers' => [
                'ref_no' => 'required|string',
                'name' => 'required|string',
            ],
            'invoices' => [
                'supplier_ref' => 'required|string',
                'invoice_ref' => 'required|string',
                'amount' => 'required|numeric',
                'invoice_date' => 'required|date',
            ],
            'risks' => [
                'ref_no' => 'required|string',
                'theme_code' => 'required|string',
                'category_code' => 'required|string',
                'name' => 'required|string',
            ],
            'governance' => [
                'ref_no' => 'required|string',
                'department' => 'required|string',
            ],
            default => [],
        };
    }

    /**
     * Map row values to field names
     */
    private function mapRowToFields(array $row, array $mapping): array
    {
        $result = [];

        foreach ($mapping as $colIndex => $fieldName) {
            $result[$fieldName] = $row[$colIndex] ?? null;
        }

        return $result;
    }

    /**
     * Get export data for type
     */
    private function getExportData(string $type, Request $request): array
    {
        return match ($type) {
            'workitems' => \App\Models\WorkItem::query()
                ->with(['responsibleParty', 'departmentHead'])
                ->get()
                ->map(fn ($item) => [
                    'ref_no' => $item->ref_no,
                    'type' => $item->type,
                    'activity' => $item->activity,
                    'department' => $item->department,
                    'description' => $item->description,
                    'goal' => $item->goal,
                    'bau_or_transformative' => $item->bau_or_transformative?->value,
                    'impact_level' => $item->impact_level?->value,
                    'current_status' => $item->current_status?->value,
                    'rag_status' => $item->rag_status?->value,
                    'deadline' => $item->deadline?->toDateString(),
                    'completion_date' => $item->completion_date?->toDateString(),
                    'monthly_update' => $item->monthly_update,
                    'comments' => $item->comments,
                    'update_frequency' => $item->update_frequency?->value,
                    'responsible_party' => $item->responsibleParty?->full_name,
                    'department_head' => $item->departmentHead?->full_name,
                    'tags' => implode(', ', $item->tags ?? []),
                    'priority_item' => $item->priority_item ? 'Yes' : 'No',
                    'cost_savings' => $item->cost_savings,
                    'cost_efficiency_fte' => $item->cost_efficiency_fte,
                    'expected_cost' => $item->expected_cost,
                    'revenue_potential' => $item->revenue_potential,
                ])
                ->toArray(),
            'suppliers' => \App\Models\Supplier::query()
                ->with(['sageCategory', 'responsibleParty', 'entities'])
                ->get()
                ->map(fn ($item) => [
                    'ref_no' => $item->ref_no,
                    'name' => $item->name,
                    'sage_category' => $item->sageCategory?->name,
                    'location' => $item->location?->value,
                    'is_common_provider' => $item->is_common_provider ? 'Yes' : 'No',
                    'status' => $item->status?->value,
                    'responsible_party' => $item->responsibleParty?->full_name,
                    'entities' => $item->entities->pluck('entity')->implode(', '),
                    'notes' => $item->notes,
                ])
                ->toArray(),
            'risks' => \App\Models\Risk::query()
                ->with(['category.theme', 'owner', 'responsibleParty'])
                ->get()
                ->map(fn ($item) => [
                    'ref_no' => $item->ref_no,
                    'theme' => $item->category?->theme?->name,
                    'category' => $item->category?->name,
                    'name' => $item->name,
                    'description' => $item->description,
                    'tier' => $item->tier?->value,
                    'owner' => $item->owner?->full_name,
                    'responsible_party' => $item->responsibleParty?->full_name,
                    'financial_impact' => $item->financial_impact,
                    'regulatory_impact' => $item->regulatory_impact,
                    'reputational_impact' => $item->reputational_impact,
                    'inherent_probability' => $item->inherent_probability,
                    'inherent_risk_score' => $item->inherent_risk_score,
                    'inherent_rag' => $item->inherent_rag?->value,
                    'residual_risk_score' => $item->residual_risk_score,
                    'residual_rag' => $item->residual_rag?->value,
                    'appetite_status' => $item->appetite_status?->value,
                ])
                ->toArray(),
            'governance' => \App\Models\GovernanceItem::query()
                ->with('responsibleParty')
                ->get()
                ->map(fn ($item) => [
                    'ref_no' => $item->ref_no,
                    'activity' => $item->activity,
                    'description' => $item->description,
                    'department' => $item->department,
                    'frequency' => $item->frequency?->value,
                    'location' => $item->location?->value,
                    'current_status' => $item->current_status?->value,
                    'rag_status' => $item->rag_status?->value,
                    'deadline' => $item->deadline?->toDateString(),
                    'completion_date' => $item->completion_date?->toDateString(),
                    'responsible_party' => $item->responsibleParty?->full_name,
                    'monthly_update' => $item->monthly_update,
                    'tags' => implode(', ', $item->tags ?? []),
                ])
                ->toArray(),
            'invoices' => \App\Models\SupplierInvoice::query()
                ->with('supplier:id,name')
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(fn ($item) => [
                    'supplier_name' => $item->supplier?->name,
                    'invoice_ref' => $item->invoice_ref,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'currency' => $item->currency,
                    'invoice_date' => $item->invoice_date?->toDateString(),
                    'due_date' => $item->due_date?->toDateString(),
                    'paid_date' => $item->paid_date?->toDateString(),
                    'status' => $item->status?->value ?? $item->status,
                    'frequency' => $item->frequency?->value ?? $item->frequency,
                    'notes' => $item->notes,
                ])
                ->toArray(),
            default => [],
        };
    }
}
