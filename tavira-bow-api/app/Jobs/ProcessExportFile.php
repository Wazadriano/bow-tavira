<?php

namespace App\Jobs;

use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\WorkItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProcessExportFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public string $type,
        public string $jobId,
        public int $userId,
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        Cache::put("export_progress_{$this->jobId}", [
            'status' => 'processing',
            'type' => $this->type,
        ], 3600);

        try {
            $data = $this->getExportData();

            if (empty($data)) {
                Cache::put("export_progress_{$this->jobId}", [
                    'status' => 'completed',
                    'type' => $this->type,
                    'rows' => 0,
                    'file' => null,
                    'message' => 'No data to export',
                ], 3600);

                return;
            }

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            $headers = array_keys($data[0]);
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }

            foreach ($data as $rowNum => $row) {
                foreach (array_values($row) as $col => $value) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $rowNum + 2, $value);
                }
            }

            $lastCol = chr(65 + min(count($headers) - 1, 25));
            $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = "{$this->type}_export_".date('Y-m-d_His').'.xlsx';
            $storagePath = "exports/{$this->jobId}/{$filename}";
            $tempPath = storage_path("app/{$storagePath}");

            if (! file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            Cache::put("export_progress_{$this->jobId}", [
                'status' => 'completed',
                'type' => $this->type,
                'rows' => count($data),
                'file' => $storagePath,
                'filename' => $filename,
            ], 3600);

        } catch (\Throwable $e) {
            Log::error("Export failed for job {$this->jobId}: {$e->getMessage()}");

            Cache::put("export_progress_{$this->jobId}", [
                'status' => 'failed',
                'type' => $this->type,
                'message' => 'Export failed: '.$e->getMessage(),
            ], 3600);
        }
    }

    private function getExportData(): array
    {
        return match ($this->type) {
            'workitems' => WorkItem::query()
                ->with(['responsibleParty', 'departmentHead', 'backUpPerson'])
                ->get()
                ->map(function ($item) {
                    /** @var WorkItem $item */
                    return [
                        'ref_no' => $item->ref_no,
                        'type' => $item->type,
                        'activity' => $item->activity,
                        'department' => $item->department,
                        'description' => $item->description,
                        'goal' => $item->goal,
                        'bau_or_transformative' => $item->bau_or_transformative !== null ? $item->bau_or_transformative->value : null,
                        'impact_level' => $item->impact_level !== null ? $item->impact_level->value : null,
                        'current_status' => $item->current_status !== null ? $item->current_status->value : null,
                        'rag_status' => $item->rag_status !== null ? $item->rag_status->value : null,
                        'deadline' => $item->deadline?->toDateString(),
                        'completion_date' => $item->completion_date?->toDateString(),
                        'monthly_update' => $item->monthly_update,
                        'comments' => $item->comments,
                        'update_frequency' => $item->update_frequency !== null ? $item->update_frequency->value : null,
                        'responsible_party' => $item->responsibleParty?->full_name,
                        'department_head' => $item->departmentHead?->full_name,
                        'back_up_person' => $item->backUpPerson?->full_name,
                        'tags' => implode(', ', $item->tags ?? []),
                        'priority_item' => $item->priority_item ? 'Yes' : 'No',
                        'cost_savings' => $item->cost_savings,
                        'cost_efficiency_fte' => $item->cost_efficiency_fte,
                        'expected_cost' => $item->expected_cost,
                        'revenue_potential' => $item->revenue_potential,
                        'other_item_completion_dependences' => $item->other_item_completion_dependences,
                        'issues_risks' => $item->issues_risks,
                        'initial_item_provider_editor' => $item->initial_item_provider_editor,
                    ];
                })
                ->toArray(),
            'suppliers' => Supplier::query()
                ->with(['sageCategory', 'responsibleParty', 'entities'])
                ->get()
                ->map(function ($item) {
                    /** @var Supplier $item */
                    return [
                        'ref_no' => $item->ref_no,
                        'name' => $item->name,
                        'sage_category' => $item->sageCategory?->name,
                        'location' => $item->location !== null ? $item->location->value : null,
                        'is_common_provider' => $item->is_common_provider ? 'Yes' : 'No',
                        'status' => $item->status !== null ? $item->status->value : null,
                        'responsible_party' => $item->responsibleParty?->full_name,
                        'entities' => $item->entities->pluck('entity')->implode(', '),
                        'notes' => $item->notes,
                    ];
                })
                ->toArray(),
            'risks' => Risk::query()
                ->with(['category.theme', 'owner', 'responsibleParty'])
                ->get()
                ->map(function ($item) {
                    /** @var Risk $item */
                    return [
                        'ref_no' => $item->ref_no,
                        'theme' => $item->category?->theme?->name,
                        'category' => $item->category?->name,
                        'name' => $item->name,
                        'description' => $item->description,
                        'tier' => $item->tier !== null ? $item->tier->value : null,
                        'owner' => $item->owner?->full_name,
                        'responsible_party' => $item->responsibleParty?->full_name,
                        'financial_impact' => $item->financial_impact,
                        'regulatory_impact' => $item->regulatory_impact,
                        'reputational_impact' => $item->reputational_impact,
                        'inherent_probability' => $item->inherent_probability,
                        'inherent_risk_score' => $item->inherent_risk_score,
                        'inherent_rag' => $item->inherent_rag !== null ? $item->inherent_rag->value : null,
                        'residual_risk_score' => $item->residual_risk_score,
                        'residual_rag' => $item->residual_rag !== null ? $item->residual_rag->value : null,
                        'appetite_status' => $item->appetite_status !== null ? $item->appetite_status->value : null,
                    ];
                })
                ->toArray(),
            'governance' => GovernanceItem::query()
                ->with('responsibleParty')
                ->get()
                ->map(function ($item) {
                    /** @var GovernanceItem $item */
                    return [
                        'ref_no' => $item->ref_no,
                        'activity' => $item->activity,
                        'description' => $item->description,
                        'department' => $item->department,
                        'frequency' => $item->frequency !== null ? $item->frequency->value : null,
                        'location' => $item->location !== null ? $item->location->value : null,
                        'current_status' => $item->current_status !== null ? $item->current_status->value : null,
                        'rag_status' => $item->rag_status !== null ? $item->rag_status->value : null,
                        'deadline' => $item->deadline?->toDateString(),
                        'completion_date' => $item->completion_date?->toDateString(),
                        'responsible_party' => $item->responsibleParty?->full_name,
                        'monthly_update' => $item->monthly_update,
                        'tags' => implode(', ', $item->tags ?? []),
                    ];
                })
                ->toArray(),
            'invoices' => SupplierInvoice::query()
                ->with('supplier:id,name')
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(function ($item) {
                    /** @var SupplierInvoice $item */
                    return [
                        'supplier_name' => $item->supplier?->name,
                        'invoice_ref' => $item->invoice_ref,
                        'description' => $item->description,
                        'amount' => $item->amount,
                        'currency' => $item->currency,
                        'invoice_date' => $item->invoice_date?->toDateString(),
                        'due_date' => $item->due_date?->toDateString(),
                        'paid_date' => $item->paid_date?->toDateString(),
                        'status' => $item->status !== null ? $item->status->value : null,
                        'frequency' => $item->frequency !== null ? $item->frequency->value : null,
                        'notes' => $item->notes,
                    ];
                })
                ->toArray(),
            default => [],
        };
    }
}
