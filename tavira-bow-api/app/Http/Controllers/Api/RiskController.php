<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiskResource;
use App\Models\Risk;
use App\Models\RiskCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RiskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Risk::class, 'risk');
    }

    /**
     * List all risks
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Risk::query()
            ->with(['category.theme', 'owner', 'responsibleParty']);

        // Filter by user's theme permissions
        if (! $user->isAdmin()) {
            $themeIds = $user->riskThemePermissions()
                ->where('can_view', true)
                ->pluck('theme_id');

            $query->whereHas('category', function ($q) use ($themeIds) {
                $q->whereIn('theme_id', $themeIds);
            });
        }

        // Filters
        if ($request->has('theme_id')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('theme_id', $request->theme_id);
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('tier')) {
            $query->where('tier', $request->tier);
        }

        if ($request->has('inherent_rag')) {
            $query->where('inherent_rag', $request->inherent_rag);
        }

        if ($request->has('residual_rag')) {
            $query->where('residual_rag', $request->residual_rag);
        }

        if ($request->has('appetite_status')) {
            $query->where('appetite_status', $request->appetite_status);
        }

        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ref_no', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'inherent_risk_score');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 25), 100);

        return RiskResource::collection($query->paginate($perPage));
    }

    /**
     * Create new risk
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:risk_categories,id',
            'ref_no' => 'required|string|max:50|unique:risks,ref_no',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'tier' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'responsible_party_id' => 'nullable|exists:users,id',
            'financial_impact' => 'nullable|integer|min:1|max:5',
            'regulatory_impact' => 'nullable|integer|min:1|max:5',
            'reputational_impact' => 'nullable|integer|min:1|max:5',
            'inherent_probability' => 'nullable|integer|min:1|max:5',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check theme permission
        $category = RiskCategory::findOrFail($request->category_id);
        $this->authorize('createInTheme', [Risk::class, $category->theme_id]);

        $risk = DB::transaction(function () use ($request) {
            $risk = Risk::create($request->all());
            $risk->calculateScores();
            $risk->save();

            return $risk;
        });

        $risk->load(['category.theme', 'owner', 'responsibleParty']);

        return response()->json([
            'message' => 'Risk created successfully',
            'risk' => new RiskResource($risk),
        ], 201);
    }

    /**
     * Get single risk
     */
    public function show(Risk $risk): JsonResponse
    {
        $risk->load([
            'category.theme',
            'owner',
            'responsibleParty',
            'controls.control',
            'actions.owner',
            'attachments.uploader',
            'workItems',
            'governanceItems',
        ]);

        return response()->json([
            'risk' => new RiskResource($risk),
        ]);
    }

    /**
     * Update risk
     */
    public function update(Request $request, Risk $risk): JsonResponse
    {
        $request->validate([
            'category_id' => 'sometimes|exists:risk_categories,id',
            'ref_no' => 'sometimes|string|max:50|unique:risks,ref_no,'.$risk->id,
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'tier' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'responsible_party_id' => 'nullable|exists:users,id',
            'financial_impact' => 'nullable|integer|min:1|max:5',
            'regulatory_impact' => 'nullable|integer|min:1|max:5',
            'reputational_impact' => 'nullable|integer|min:1|max:5',
            'inherent_probability' => 'nullable|integer|min:1|max:5',
            'monthly_update' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($request, $risk) {
            $risk->update($request->all());
            $risk->calculateScores();
            $risk->save();
        });

        $risk->load(['category.theme', 'owner', 'responsibleParty']);

        return response()->json([
            'message' => 'Risk updated successfully',
            'risk' => new RiskResource($risk),
        ]);
    }

    /**
     * Delete risk
     */
    public function destroy(Risk $risk): JsonResponse
    {
        $risk->delete();

        return response()->json([
            'message' => 'Risk deleted successfully',
        ]);
    }

    /**
     * Get risk heatmap data
     */
    public function heatmap(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Risk::query()
            ->where('is_active', true)
            ->select('financial_impact', 'regulatory_impact', 'reputational_impact', 'inherent_probability', 'ref_no', 'name', 'inherent_risk_score', 'inherent_rag');

        // Filter by user's theme permissions
        if (! $user->isAdmin()) {
            $themeIds = $user->riskThemePermissions()
                ->where('can_view', true)
                ->pluck('theme_id');

            $query->whereHas('category', function ($q) use ($themeIds) {
                $q->whereIn('theme_id', $themeIds);
            });
        }

        if ($request->has('theme_id')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('theme_id', $request->theme_id);
            });
        }

        $risks = $query->get();

        // Build heatmap matrix (5x5)
        $matrix = [];
        for ($impact = 1; $impact <= 5; $impact++) {
            for ($probability = 1; $probability <= 5; $probability++) {
                $matrix["{$impact}-{$probability}"] = [
                    'impact' => $impact,
                    'probability' => $probability,
                    'score' => $impact * $probability,
                    'risks' => [],
                ];
            }
        }

        // Place risks in matrix
        foreach ($risks as $risk) {
            $impact = max($risk->financial_impact ?? 1, $risk->regulatory_impact ?? 1, $risk->reputational_impact ?? 1);
            $probability = $risk->inherent_probability ?? 1;
            $key = "{$impact}-{$probability}";

            $matrix[$key]['risks'][] = [
                'ref_no' => $risk->ref_no,
                'name' => $risk->name,
                'score' => $risk->inherent_risk_score,
                'rag' => $risk->inherent_rag?->value,
            ];
        }

        return response()->json([
            'heatmap' => array_values($matrix),
            'total_risks' => $risks->count(),
        ]);
    }

    /**
     * Recalculate all risk scores
     */
    public function recalculate(): JsonResponse
    {
        $count = 0;

        DB::transaction(function () use (&$count) {
            Risk::chunk(100, function ($risks) use (&$count) {
                foreach ($risks as $risk) {
                    $risk->calculateScores();
                    $risk->save();
                    $count++;
                }
            });
        });

        return response()->json([
            'message' => "Recalculated scores for {$count} risks",
            'count' => $count,
        ]);
    }
}
