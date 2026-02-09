<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportFile;
use App\Services\ImportNormalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'type' => 'required|in:workitems,suppliers,invoices,risks',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        // Parse file content
        if (in_array($extension, ['xlsx', 'xls'])) {
            $data = $this->parseExcel($file->getPathname());
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
        $expectedColumns = $this->getExpectedColumns($request->type);

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
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'temp_file' => 'required|string',
            'type' => 'required|in:workitems,suppliers,invoices,risks',
            'column_mapping' => 'required|array',
        ]);

        if (! Storage::exists($request->temp_file)) {
            return response()->json([
                'message' => 'Temporary file not found. Please upload again.',
            ], 404);
        }

        // Queue the import job
        ProcessImportFile::dispatch(
            $request->temp_file,
            $request->type,
            $request->column_mapping,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Import queued successfully. You will be notified when complete.',
            'job_status' => 'queued',
        ], 202);
    }

    /**
     * Download import template
     */
    public function template(string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $templates = [
            'workitems' => [
                'ref_no', 'type', 'activity', 'department', 'description',
                'bau_or_transformative', 'impact_level', 'current_status',
                'deadline', 'responsible_party', 'tags', 'priority_item',
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
        $headerRange = 'A1:'.chr(65 + count($templates[$type]) - 1).'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', chr(65 + count($templates[$type]) - 1)) as $col) {
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
     * Export data
     */
    public function export(Request $request, string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
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
        $headerRange = 'A1:'.chr(65 + count($headers) - 1).'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', chr(65 + min(count($headers) - 1, 25))) as $col) {
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
     * Parse Excel file
     */
    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }

    /**
     * Get expected columns for import type
     */
    private function getExpectedColumns(string $type): array
    {
        return match ($type) {
            'workitems' => [
                'ref_no' => 'ref_no',
                'reference' => 'ref_no',
                'type' => 'type',
                'activity' => 'activity',
                'department' => 'department',
                'description' => 'description',
                'bau_or_transformative' => 'bau_or_transformative',
                'bau' => 'bau_or_transformative',
                'impact_level' => 'impact_level',
                'impact' => 'impact_level',
                'current_status' => 'current_status',
                'status' => 'current_status',
                'deadline' => 'deadline',
                'due_date' => 'deadline',
                'responsible_party' => 'responsible_party_id',
                'owner' => 'responsible_party_id',
                'tags' => 'tags',
                'priority_item' => 'priority_item',
                'priority' => 'priority_item',
            ],
            'suppliers' => [
                'ref_no' => 'ref_no',
                'reference' => 'ref_no',
                'name' => 'name',
                'supplier_name' => 'name',
                'sage_category' => 'sage_category_id',
                'category' => 'sage_category_id',
                'location' => 'location',
                'is_common_provider' => 'is_common_provider',
                'common_provider' => 'is_common_provider',
                'status' => 'status',
                'entities' => 'entities',
                'notes' => 'notes',
            ],
            'invoices' => [
                'supplier_ref' => 'supplier_ref',
                'supplier' => 'supplier_ref',
                'invoice_ref' => 'invoice_ref',
                'invoice_number' => 'invoice_ref',
                'description' => 'description',
                'amount' => 'amount',
                'currency' => 'currency',
                'invoice_date' => 'invoice_date',
                'date' => 'invoice_date',
                'due_date' => 'due_date',
                'frequency' => 'frequency',
                'status' => 'status',
            ],
            'risks' => [
                'ref_no' => 'ref_no',
                'reference' => 'ref_no',
                'theme_code' => 'theme_code',
                'theme' => 'theme_code',
                'category_code' => 'category_code',
                'category' => 'category_code',
                'name' => 'name',
                'description' => 'description',
                'tier' => 'tier',
                'owner' => 'owner_id',
                'responsible_party' => 'responsible_party_id',
                'financial_impact' => 'financial_impact',
                'regulatory_impact' => 'regulatory_impact',
                'reputational_impact' => 'reputational_impact',
                'inherent_probability' => 'inherent_probability',
                'probability' => 'inherent_probability',
            ],
            default => [],
        };
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
                ->with('responsibleParty')
                ->get()
                ->map(fn ($item) => [
                    'ref_no' => $item->ref_no,
                    'type' => $item->type,
                    'activity' => $item->activity,
                    'department' => $item->department,
                    'description' => $item->description,
                    'bau_or_transformative' => $item->bau_or_transformative?->value,
                    'impact_level' => $item->impact_level?->value,
                    'current_status' => $item->current_status?->value,
                    'rag_status' => $item->rag_status?->value,
                    'deadline' => $item->deadline?->toDateString(),
                    'completion_date' => $item->completion_date?->toDateString(),
                    'responsible_party' => $item->responsibleParty?->full_name,
                    'tags' => implode(', ', $item->tags ?? []),
                    'priority_item' => $item->priority_item ? 'Yes' : 'No',
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
            default => [],
        };
    }
}
