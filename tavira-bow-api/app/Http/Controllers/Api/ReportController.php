<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\Supplier;
use App\Models\WorkItem;
use App\Services\CurrencyConversionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly CurrencyConversionService $currencyService
    ) {}

    public function workItemsReport(Request $request): Response
    {
        $query = WorkItem::with('responsibleParty');

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('status')) {
            $query->where('current_status', $request->status);
        }

        $items = $query->orderBy('deadline')->get();

        $pdf = Pdf::loadView('reports.work-items', [
            'items' => $items,
            'title' => 'Work Items Report',
            'generated_at' => now(),
            'filters' => $request->only(['department', 'status']),
        ]);

        return $pdf->download('work-items-report.pdf');
    }

    public function risksReport(Request $request): Response
    {
        $query = Risk::with(['category.theme', 'owner']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $risks = $query->orderByDesc('inherent_risk_score')->get();

        $pdf = Pdf::loadView('reports.risks', [
            'risks' => $risks,
            'title' => 'Risk Register Report',
            'generated_at' => now(),
        ]);

        return $pdf->download('risks-report.pdf');
    }

    public function suppliersReport(Request $request): Response
    {
        $query = Supplier::with(['contracts', 'invoices']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->orderBy('name')->get();

        $pdf = Pdf::loadView('reports.suppliers', [
            'suppliers' => $suppliers,
            'title' => 'Suppliers Report',
            'generated_at' => now(),
            'currencyService' => $this->currencyService,
        ]);

        return $pdf->download('suppliers-report.pdf');
    }

    public function governanceReport(Request $request): Response
    {
        $query = GovernanceItem::with('responsibleParty');

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        $items = $query->orderBy('deadline')->get();

        $pdf = Pdf::loadView('reports.governance', [
            'items' => $items,
            'title' => 'Governance Report',
            'generated_at' => now(),
        ]);

        return $pdf->download('governance-report.pdf');
    }
}
