<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Import\ConfirmImportRequest;
use App\Http\Requests\Import\PreviewImportRequest;
use App\Jobs\ProcessExportFile;
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
            // Suppress PhpSpreadsheet warnings on complex Excel files with empty XML parts
            $spreadsheet = @IOFactory::load($file->getPathname());
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

            // Get data from selected sheet (reuse loaded spreadsheet)
            $activeSheet = $selectedSheet
                ? ($spreadsheet->getSheetByName($selectedSheet) ?? $spreadsheet->getActiveSheet())
                : $spreadsheet->getActiveSheet();
            $data = $activeSheet->toArray(null, true, true, false);
            $data = $this->importService->sanitizeExcelData($data);
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
     * Export work items (async)
     */
    public function exportWorkItems(Request $request): JsonResponse
    {
        return $this->dispatchExport($request, 'workitems');
    }

    /**
     * Export governance items (async)
     */
    public function exportGovernance(Request $request): JsonResponse
    {
        return $this->dispatchExport($request, 'governance');
    }

    /**
     * Export suppliers (async)
     */
    public function exportSuppliers(Request $request): JsonResponse
    {
        return $this->dispatchExport($request, 'suppliers');
    }

    /**
     * Export risks (async)
     */
    public function exportRisks(Request $request): JsonResponse
    {
        return $this->dispatchExport($request, 'risks');
    }

    /**
     * Export invoices (async)
     */
    public function exportInvoices(Request $request): JsonResponse
    {
        return $this->dispatchExport($request, 'invoices');
    }

    /**
     * Get export job status
     */
    public function exportStatus(string $jobId): JsonResponse
    {
        $progress = Cache::get("export_progress_{$jobId}");

        if (! $progress) {
            return response()->json([
                'status' => 'unknown',
                'message' => 'Job not found or not yet started.',
            ]);
        }

        return response()->json($progress);
    }

    /**
     * Download completed export file
     */
    public function exportDownload(string $jobId): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $progress = Cache::get("export_progress_{$jobId}");

        if (! $progress || $progress['status'] !== 'completed' || empty($progress['file'])) {
            return response()->json(['message' => 'Export not ready or not found.'], 404);
        }

        $filePath = storage_path("app/{$progress['file']}");

        if (! file_exists($filePath)) {
            return response()->json(['message' => 'Export file not found.'], 404);
        }

        return response()->download($filePath, $progress['filename'] ?? basename($filePath));
    }

    /**
     * Dispatch async export job
     */
    private function dispatchExport(Request $request, string $type): JsonResponse
    {
        $jobId = uniqid('export_', true);

        ProcessExportFile::dispatch($type, $jobId, $request->user()->id);

        return response()->json([
            'message' => 'Export queued successfully.',
            'job_id' => $jobId,
            'job_status' => 'queued',
        ], 202);
    }

    /**
     * Parse Excel file with optional sheet name selection
     */
    private function parseExcel(string $path, ?string $sheetName = null): array
    {
        $spreadsheet = @IOFactory::load($path);

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
}
