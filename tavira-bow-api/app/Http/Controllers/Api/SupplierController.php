<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\SupplierAccess;
use App\Models\SupplierEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Supplier::class, 'supplier');
    }

    /**
     * List all suppliers
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Supplier::query()
            ->with(['sageCategory', 'responsibleParty', 'entities']);

        // Filter by user access
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('access', function ($q2) use ($user) {
                    $q2->where('user_id', $user->id)->where('can_view', true);
                })
                    ->orWhere('responsible_party_id', $user->id);
            });
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('location')) {
            $query->where('location', $request->location);
        }

        if ($request->has('sage_category_id')) {
            $query->where('sage_category_id', $request->sage_category_id);
        }

        if ($request->has('is_common_provider')) {
            $query->where('is_common_provider', filter_var($request->is_common_provider, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('entity')) {
            $query->whereHas('entities', function ($q) use ($request) {
                $q->where('entity', $request->entity);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ref_no', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 25), 100);

        return SupplierResource::collection($query->paginate($perPage));
    }

    /**
     * Create new supplier
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ref_no' => 'required|string|max:50|unique:suppliers,ref_no',
            'name' => 'required|string|max:200',
            'sage_category_id' => 'nullable|exists:sage_categories,id',
            'responsible_party_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string',
            'is_common_provider' => 'sometimes|boolean',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
        ]);

        $supplier = DB::transaction(function () use ($request) {
            $supplier = Supplier::create($request->except('entities'));

            // Add entities
            if ($request->has('entities')) {
                foreach ($request->entities as $entity) {
                    SupplierEntity::create([
                        'supplier_id' => $supplier->id,
                        'entity' => $entity,
                    ]);
                }
            }

            return $supplier;
        });

        $supplier->load(['sageCategory', 'responsibleParty', 'entities']);

        return response()->json([
            'message' => 'Supplier created successfully',
            'supplier' => new SupplierResource($supplier),
        ], 201);
    }

    /**
     * Get single supplier
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->load([
            'sageCategory',
            'responsibleParty',
            'entities',
            'contracts.entities',
            'invoices',
            'attachments.uploader',
            'access.user',
        ]);

        return response()->json([
            'supplier' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Update supplier
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $request->validate([
            'ref_no' => 'sometimes|string|max:50|unique:suppliers,ref_no,'.$supplier->id,
            'name' => 'sometimes|string|max:200',
            'sage_category_id' => 'nullable|exists:sage_categories,id',
            'responsible_party_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string',
            'is_common_provider' => 'sometimes|boolean',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
            'entities' => 'nullable|array',
            'entities.*' => 'string',
        ]);

        DB::transaction(function () use ($request, $supplier) {
            $supplier->update($request->except('entities'));

            // Update entities if provided
            if ($request->has('entities')) {
                $supplier->entities()->delete();
                foreach ($request->entities as $entity) {
                    SupplierEntity::create([
                        'supplier_id' => $supplier->id,
                        'entity' => $entity,
                    ]);
                }
            }
        });

        $supplier->load(['sageCategory', 'responsibleParty', 'entities']);

        return response()->json([
            'message' => 'Supplier updated successfully',
            'supplier' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Delete supplier
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully',
        ]);
    }

    /**
     * Manage access to supplier
     */
    public function manageAccess(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $request->validate([
            'users' => 'required|array',
            'users.*.user_id' => 'required|exists:users,id',
            'users.*.can_view' => 'required|boolean',
            'users.*.can_edit' => 'required|boolean',
            'users.*.can_create' => 'required|boolean',
            'users.*.can_delete' => 'required|boolean',
        ]);

        DB::transaction(function () use ($request, $supplier) {
            $supplier->access()->delete();

            foreach ($request->users as $access) {
                SupplierAccess::create([
                    'supplier_id' => $supplier->id,
                    'user_id' => $access['user_id'],
                    'can_view' => $access['can_view'],
                    'can_edit' => $access['can_edit'],
                    'can_create' => $access['can_create'],
                    'can_delete' => $access['can_delete'],
                ]);
            }
        });

        $supplier->load('access.user');

        return response()->json([
            'message' => 'Access updated successfully',
            'access' => $supplier->access,
        ]);
    }

    /**
     * Get supplier statistics
     */
    public function stats(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return response()->json([
            'total_contracts' => $supplier->contracts()->count(),
            'active_contracts' => $supplier->contracts()->active()->count(),
            'total_invoices' => $supplier->invoices()->count(),
            'total_invoices_amount' => $supplier->invoices()->sum('amount'),
            'pending_invoices' => $supplier->invoices()->pending()->count(),
            'pending_invoices_amount' => $supplier->invoices()->pending()->sum('amount'),
        ]);
    }
}
