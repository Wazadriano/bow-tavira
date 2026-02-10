<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierInvoiceResource;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierInvoiceController extends Controller
{
    /**
     * List all invoices across all suppliers (global listing)
     */
    public function all(Request $request): AnonymousResourceCollection
    {
        $refColumn = Schema::hasColumn((new SupplierInvoice)->getTable(), 'invoice_ref')
            ? 'invoice_ref'
            : 'invoice_number';

        $query = SupplierInvoice::with(['supplier:id,name', 'sageCategory']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $refColumn) {
                $q->where($refColumn, 'ilike', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"));
            });
        }
        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        return SupplierInvoiceResource::collection(
            $query->orderBy('invoice_date', 'desc')->paginate($request->per_page ?? 20)
        );
    }

    /**
     * List invoices for a supplier
     */
    public function index(Request $request, Supplier $supplier): AnonymousResourceCollection
    {
        $this->authorize('view', $supplier);

        $query = $supplier->invoices()->with('sageCategory');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        if ($request->has('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->overdue();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'invoice_date');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        return SupplierInvoiceResource::collection($query->paginate(25));
    }

    /**
     * Create new invoice
     */
    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $request->validate([
            'invoice_ref' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'paid_date' => 'nullable|date',
            'frequency' => 'nullable|string',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $invoice = SupplierInvoice::create([
            'supplier_id' => $supplier->id,
            ...$request->all(),
        ]);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => new SupplierInvoiceResource($invoice),
        ], 201);
    }

    /**
     * Get single invoice
     */
    public function show(Supplier $supplier, SupplierInvoice $invoice): JsonResponse
    {
        $this->authorize('view', $supplier);

        if ($invoice->supplier_id !== $supplier->id) {
            abort(404);
        }

        return response()->json([
            'invoice' => new SupplierInvoiceResource($invoice),
        ]);
    }

    /**
     * Update invoice
     */
    public function update(Request $request, Supplier $supplier, SupplierInvoice $invoice): JsonResponse
    {
        $this->authorize('update', $supplier);

        if ($invoice->supplier_id !== $supplier->id) {
            abort(404);
        }

        $request->validate([
            'invoice_ref' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'nullable|date',
            'paid_date' => 'nullable|date',
            'frequency' => 'nullable|string',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($request->all());

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => new SupplierInvoiceResource($invoice),
        ]);
    }

    /**
     * Delete invoice
     */
    public function destroy(Supplier $supplier, SupplierInvoice $invoice): JsonResponse
    {
        $this->authorize('delete', $supplier);

        if ($invoice->supplier_id !== $supplier->id) {
            abort(404);
        }

        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }

    /**
     * Bulk import invoices
     */
    public function bulkImport(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $request->validate([
            'invoices' => 'required|array|min:1',
            'invoices.*.invoice_ref' => 'required|string|max:100',
            'invoices.*.amount' => 'required|numeric|min:0',
            'invoices.*.invoice_date' => 'required|date',
            'invoices.*.due_date' => 'nullable|date',
            'invoices.*.description' => 'nullable|string',
            'invoices.*.status' => 'nullable|string',
        ]);

        $created = 0;
        $errors = [];

        DB::transaction(function () use ($request, $supplier, &$created, &$errors) {
            foreach ($request->invoices as $index => $invoiceData) {
                try {
                    SupplierInvoice::create([
                        'supplier_id' => $supplier->id,
                        ...$invoiceData,
                    ]);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'ref' => $invoiceData['invoice_ref'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'message' => "Bulk import completed: {$created} invoices created",
            'created' => $created,
            'errors' => $errors,
        ], count($errors) > 0 ? 207 : 201);
    }

    /**
     * Mark invoice as paid
     */
    public function markPaid(Request $request, Supplier $supplier, SupplierInvoice $invoice): JsonResponse
    {
        $this->authorize('update', $supplier);

        if ($invoice->supplier_id !== $supplier->id) {
            abort(404);
        }

        $request->validate([
            'paid_date' => 'nullable|date',
        ]);

        $invoice->update([
            'status' => 'Paid',
            'paid_date' => $request->paid_date ?? now(),
        ]);

        return response()->json([
            'message' => 'Invoice marked as paid',
            'invoice' => new SupplierInvoiceResource($invoice),
        ]);
    }
}
