<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\SupplierAccess;
use App\Models\SupplierEntity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
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
            ->with(['sageCategory', 'responsibleParty', 'entities', 'contracts', 'invoices']);

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
    public function store(StoreSupplierRequest $request): JsonResponse
    {
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
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
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
     * Add access for a user to a supplier (route: POST suppliers/{supplier}/access)
     */
    public function addAccess(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'access_level' => 'required|in:read,write,admin',
        ]);

        $canEdit = $validated['access_level'] !== 'read';
        $access = $supplier->access()->create([
            'user_id' => $validated['user_id'],
            'can_view' => true,
            'can_edit' => $canEdit,
        ]);

        return response()->json(['access' => $access->load('user')], 201);
    }

    /**
     * Remove access from a supplier (route: DELETE suppliers/{supplier}/access/{access})
     */
    public function removeAccess(Supplier $supplier, SupplierAccess $access): JsonResponse
    {
        $this->authorize('update', $supplier);

        if ($access->supplier_id !== $supplier->id) {
            return response()->json(['message' => 'Access does not belong to this supplier'], 403);
        }

        $access->delete();

        return response()->json(null, 204);
    }

    /**
     * Suppliers dashboard stats (scoped by user access)
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = 'supplier_dashboard_'.($user->isAdmin() ? 'admin' : $user->id);

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            $supplierScope = Supplier::query();
            if (! $user->isAdmin()) {
                $supplierScope->where(function ($q) use ($user) {
                    $q->whereHas('access', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id)->where('can_view', true);
                    })->orWhere('responsible_party_id', $user->id);
                });
            }

            $supplierIds = (clone $supplierScope)->pluck('id');

            $totalSuppliers = (clone $supplierScope)->count();
            $activeSuppliers = (clone $supplierScope)->active()->count();

            $contractScope = \App\Models\SupplierContract::whereIn('supplier_id', $supplierIds);
            $totalContracts = (clone $contractScope)->count();
            $expiringSoon = (clone $contractScope)->expiringSoon(90)->count();

            $invoiceScope = \App\Models\SupplierInvoice::whereIn('supplier_id', $supplierIds);
            $totalInvoices = (clone $invoiceScope)->count();
            $pendingInvoices = (clone $invoiceScope)->pending()->count();

            $byLocation = (clone $supplierScope)->selectRaw('location, count(*) as count')
                ->whereNotNull('location')
                ->groupBy('location')
                ->get()
                ->map(function ($row) {
                    /** @var Supplier $row */
                    return ['name' => $row->location !== null ? $row->location->value : 'Unknown', 'count' => (int) $row->count];
                });

            $byCategory = (clone $supplierScope)->selectRaw('sage_category_id, count(*) as count')
                ->whereNotNull('sage_category_id')
                ->groupBy('sage_category_id')
                ->orderByRaw('count(*) DESC')
                ->limit(10)
                ->with('sageCategory')
                ->get()
                ->map(function ($row) {
                    /** @var Supplier $row */
                    return ['name' => $row->sageCategory?->name ?? 'Unknown', 'count' => (int) $row->count];
                });

            $statusCounts = (clone $supplierScope)->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $byStatus = [
                'active' => (int) ($statusCounts['Active'] ?? 0),
                'inactive' => (int) ($statusCounts['Exited'] ?? 0),
                'pending' => (int) ($statusCounts['Pending'] ?? 0),
            ];

            $expiringContracts = \App\Models\SupplierContract::whereIn('supplier_id', $supplierIds)
                ->expiringSoon(90)
                ->with('supplier')
                ->orderBy('end_date')
                ->limit(10)
                ->get()
                ->map(function ($c) {
                    /** @var \App\Models\SupplierContract $c */
                    return [
                        'id' => $c->id,
                        'name' => $c->description ?? $c->contract_ref,
                        'supplier' => $c->supplier?->name ?? '',
                        'end_date' => $c->end_date?->toDateString(),
                    ];
                });

            return [
                'total_suppliers' => $totalSuppliers,
                'active_suppliers' => $activeSuppliers,
                'total_contracts' => $totalContracts,
                'expiring_soon' => $expiringSoon,
                'total_invoices' => $totalInvoices,
                'pending_invoices' => $pendingInvoices,
                'by_location' => $byLocation->values(),
                'by_category' => $byCategory->values(),
                'by_status' => $byStatus,
                'expiring_contracts' => $expiringContracts->values(),
            ];
        });

        return response()->json($data);
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
