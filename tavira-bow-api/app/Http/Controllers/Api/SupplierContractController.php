<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierContractResource;
use App\Models\ContractEntity;
use App\Models\Supplier;
use App\Models\SupplierContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SupplierContractController extends Controller
{
    /**
     * List all contracts across all suppliers (global listing)
     */
    public function all(Request $request): AnonymousResourceCollection
    {
        $query = SupplierContract::with('supplier:id,name');

        if ($request->boolean('expiring_soon')) {
            $query->where('end_date', '<=', now()->addDays(90))
                ->where('end_date', '>=', now());
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contract_ref', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"));
            });
        }

        return SupplierContractResource::collection(
            $query->orderBy('end_date', 'asc')->paginate($request->per_page ?? 20)
        );
    }

    /**
     * List contracts for a supplier
     */
    public function index(Request $request, Supplier $supplier): AnonymousResourceCollection
    {
        $this->authorize('view', $supplier);

        $query = $supplier->contracts()->with(['entities', 'attachments']);

        // Filters
        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        if ($request->has('expiring_soon')) {
            $days = (int) $request->expiring_soon;
            $query->expiringSoon($days);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'end_date');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        return SupplierContractResource::collection($query->paginate(25));
    }

    /**
     * Create new contract
     */
    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $request->validate([
            'contract_ref' => 'required|string|max:100',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'auto_renewal' => 'sometimes|boolean',
            'notice_period_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
        ]);

        $contract = DB::transaction(function () use ($request, $supplier) {
            $contract = SupplierContract::create([
                'supplier_id' => $supplier->id,
                ...$request->except('entities'),
            ]);

            // Add entities
            if ($request->has('entities')) {
                foreach ($request->entities as $entity) {
                    ContractEntity::create([
                        'contract_id' => $contract->id,
                        'entity' => $entity,
                    ]);
                }
            }

            return $contract;
        });

        $contract->load('entities');

        return response()->json([
            'message' => 'Contract created successfully',
            'contract' => new SupplierContractResource($contract),
        ], 201);
    }

    /**
     * Get single contract
     */
    public function show(Supplier $supplier, SupplierContract $contract): JsonResponse
    {
        $this->authorize('view', $supplier);

        // Ensure contract belongs to supplier
        if ($contract->supplier_id !== $supplier->id) {
            abort(404);
        }

        $contract->load(['entities', 'attachments.uploader']);

        return response()->json([
            'contract' => new SupplierContractResource($contract),
        ]);
    }

    /**
     * Update contract
     */
    public function update(Request $request, Supplier $supplier, SupplierContract $contract): JsonResponse
    {
        $this->authorize('update', $supplier);

        if ($contract->supplier_id !== $supplier->id) {
            abort(404);
        }

        $request->validate([
            'contract_ref' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'auto_renewal' => 'sometimes|boolean',
            'notice_period_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
        ]);

        DB::transaction(function () use ($request, $contract) {
            $contract->update($request->except('entities'));

            if ($request->has('entities')) {
                $contract->entities()->delete();
                foreach ($request->entities as $entity) {
                    ContractEntity::create([
                        'contract_id' => $contract->id,
                        'entity' => $entity,
                    ]);
                }
            }
        });

        $contract->load('entities');

        return response()->json([
            'message' => 'Contract updated successfully',
            'contract' => new SupplierContractResource($contract),
        ]);
    }

    /**
     * Delete contract
     */
    public function destroy(Supplier $supplier, SupplierContract $contract): JsonResponse
    {
        $this->authorize('delete', $supplier);

        if ($contract->supplier_id !== $supplier->id) {
            abort(404);
        }

        $contract->delete();

        return response()->json([
            'message' => 'Contract deleted successfully',
        ]);
    }

    /**
     * Get expiring contracts across all suppliers
     */
    public function expiring(Request $request): AnonymousResourceCollection
    {
        $days = $request->get('days', 90);

        $query = SupplierContract::query()
            ->with(['supplier', 'entities'])
            ->expiringSoon($days)
            ->orderBy('end_date');

        return SupplierContractResource::collection($query->paginate(25));
    }
}
