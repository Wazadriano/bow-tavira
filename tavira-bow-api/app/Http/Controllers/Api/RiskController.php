<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskRequest;
use App\Http\Requests\UpdateRiskRequest;
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
            ->with(['category.theme', 'owner', 'responsibleParty', 'controls']);

        // Non-admins only see their own risks (owner or responsible)
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('responsible_party_id', $user->id);
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
    public function store(StoreRiskRequest $request): JsonResponse
    {
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
    public function update(UpdateRiskRequest $request, Risk $risk): JsonResponse
    {
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
     * Risk dashboard stats
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Risk::active();

        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('responsible_party_id', $user->id);
            });
        }

        $risks = $query->get();

        $totalRisks = $risks->count();
        $highRisks = $risks->where('inherent_rag', \App\Enums\RAGStatus::RED)->count();
        $mediumRisks = $risks->where('inherent_rag', \App\Enums\RAGStatus::AMBER)->count();
        $lowRisks = $totalRisks - $highRisks - $mediumRisks;

        $riskIds = $risks->pluck('id');

        $openActions = \App\Models\RiskAction::open()
            ->whereIn('risk_id', $riskIds)->count();
        $overdueActions = \App\Models\RiskAction::overdue()
            ->whereIn('risk_id', $riskIds)->count();

        // By theme (L1) - scoped to user's visible risks
        $visibleThemeIds = $risks->map(fn ($r) => $r->category?->theme_id)->filter()->unique();
        $themeQuery = \App\Models\RiskTheme::with('categories');
        if (! $user->isAdmin()) {
            $themeQuery->whereIn('id', $visibleThemeIds);
        }

        $byTheme = $themeQuery->get()->map(function ($theme) {
            /** @var \App\Models\RiskTheme $theme */
            return [
                'name' => $theme->name,
                'code' => $theme->code ?? $theme->name,
                'count' => $theme->categories->sum(function ($c) {
                    /** @var RiskCategory $c */
                    return $c->risks()->active()->count();
                }),
            ];
        })->filter(fn ($t) => $t['count'] > 0)->values();

        // By tier
        $byTier = $risks->groupBy(function ($r) {
            /** @var Risk $r */
            return $r->tier !== null ? $r->tier->value : 'Unknown';
        })
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->values();

        // By RAG
        $byRag = [
            'blue' => $risks->where('inherent_rag', \App\Enums\RAGStatus::BLUE)->count(),
            'green' => $risks->where('inherent_rag', \App\Enums\RAGStatus::GREEN)->count(),
            'amber' => $risks->where('inherent_rag', \App\Enums\RAGStatus::AMBER)->count(),
            'red' => $risks->where('inherent_rag', \App\Enums\RAGStatus::RED)->count(),
        ];

        // Appetite breaches
        $appetiteBreaches = $risks
            ->where('appetite_status', \App\Enums\AppetiteStatus::OUTSIDE)
            ->map(function ($r) {
                /** @var Risk $r */
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'theme' => $r->category?->theme?->name ?? '',
                    'score' => (float) $r->residual_risk_score,
                    'appetite' => (float) ($r->category?->theme?->board_appetite ?? 0),
                ];
            })
            ->values();

        return response()->json([
            'total_risks' => $totalRisks,
            'high_risks' => $highRisks,
            'medium_risks' => $mediumRisks,
            'low_risks' => $lowRisks,
            'open_actions' => $openActions,
            'overdue_actions' => $overdueActions,
            'by_theme' => $byTheme,
            'by_tier' => $byTier,
            'by_rag' => $byRag,
            'appetite_breaches' => $appetiteBreaches,
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

        // Non-admins only see their own risks
        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('responsible_party_id', $user->id);
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
            /** @var Risk $risk */
            $impact = max($risk->financial_impact ?? 1, $risk->regulatory_impact ?? 1, $risk->reputational_impact ?? 1);
            $probability = $risk->inherent_probability ?? 1;
            $key = "{$impact}-{$probability}";

            $matrix[$key]['risks'][] = [
                'ref_no' => $risk->ref_no,
                'name' => $risk->name,
                'score' => $risk->inherent_risk_score,
                'rag' => $risk->inherent_rag !== null ? $risk->inherent_rag->value : null,
            ];
        }

        /** @var array<string, array{impact: int, probability: int, score: int, risks: list<array{ref_no: string|null, name: string|null, score: mixed, rag: string|null}>}> $matrix */
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

    /**
     * Recalculate scores for a single risk
     */
    public function recalculateSingle(Risk $risk): JsonResponse
    {
        $this->authorize('update', $risk);

        $risk->calculateScores();
        $risk->save();
        $risk->refresh();

        return response()->json([
            'risk' => new RiskResource($risk->load(['controls.control', 'actions', 'category.theme', 'owner'])),
        ]);
    }
}
