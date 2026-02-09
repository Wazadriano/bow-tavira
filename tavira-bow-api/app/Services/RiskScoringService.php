<?php

namespace App\Services;

use App\Enums\RAGStatus;
use App\Enums\RiskAppetiteStatus;
use App\Models\Risk;
use App\Models\RiskCategory;
use Illuminate\Support\Collection;

class RiskScoringService
{
    /**
     * Impact scale (1-5)
     */
    private const IMPACT_SCALE = [
        1 => 'Negligible',
        2 => 'Minor',
        3 => 'Moderate',
        4 => 'Major',
        5 => 'Severe',
    ];

    /**
     * Probability scale (1-5)
     */
    private const PROBABILITY_SCALE = [
        1 => 'Rare',
        2 => 'Unlikely',
        3 => 'Possible',
        4 => 'Likely',
        5 => 'Almost Certain',
    ];

    /**
     * RAG thresholds for 5x5 matrix (score ranges)
     */
    private const RAG_THRESHOLDS = [
        'green' => ['min' => 1, 'max' => 4],    // Low risk: 1-4
        'amber' => ['min' => 5, 'max' => 12],   // Medium risk: 5-12
        'red' => ['min' => 13, 'max' => 25],    // High risk: 13-25
    ];

    /**
     * Calculate inherent risk score (before controls)
     */
    public function calculateInherentScore(Risk $risk): int
    {
        $maxImpact = max(
            $risk->financial_impact ?? 0,
            $risk->regulatory_impact ?? 0,
            $risk->reputational_impact ?? 0
        );

        $probability = $risk->inherent_probability ?? 1;

        return $maxImpact * $probability;
    }

    /**
     * Calculate residual risk score (after controls)
     */
    public function calculateResidualScore(Risk $risk): int
    {
        $inherentScore = $this->calculateInherentScore($risk);

        // Get control effectiveness
        $controls = $risk->riskControls()
            ->with('control')
            ->where('is_active', true)
            ->get();

        if ($controls->isEmpty()) {
            return $inherentScore;
        }

        // Calculate total effectiveness (each control can reduce score)
        $totalEffectiveness = $controls->sum(function ($rc) {
            return match ($rc->effectiveness?->value ?? 'none') {
                'effective' => 0.3,
                'partially_effective' => 0.15,
                'ineffective', 'none' => 0,
                default => 0,
            };
        });

        // Cap effectiveness at 70% reduction
        $effectiveReduction = min($totalEffectiveness, 0.7);

        return max(1, (int) round($inherentScore * (1 - $effectiveReduction)));
    }

    /**
     * Get RAG status from score
     */
    public function getRAGFromScore(int $score): RAGStatus
    {
        if ($score <= self::RAG_THRESHOLDS['green']['max']) {
            return RAGStatus::GREEN;
        }

        if ($score <= self::RAG_THRESHOLDS['amber']['max']) {
            return RAGStatus::AMBER;
        }

        return RAGStatus::RED;
    }

    /**
     * Calculate appetite status
     */
    public function calculateAppetiteStatus(Risk $risk): RiskAppetiteStatus
    {
        $residualScore = $this->calculateResidualScore($risk);
        $appetiteThreshold = $risk->category?->risk_appetite_threshold ?? 8;

        if ($residualScore <= $appetiteThreshold) {
            return RiskAppetiteStatus::WITHIN;
        }

        if ($residualScore <= $appetiteThreshold * 1.5) {
            return RiskAppetiteStatus::APPROACHING;
        }

        return RiskAppetiteStatus::EXCEEDED;
    }

    /**
     * Update all scores for a risk
     */
    public function updateRiskScores(Risk $risk): Risk
    {
        $risk->inherent_risk_score = $this->calculateInherentScore($risk);
        $risk->inherent_rag = $this->getRAGFromScore($risk->inherent_risk_score);

        $risk->residual_risk_score = $this->calculateResidualScore($risk);
        $risk->residual_rag = $this->getRAGFromScore($risk->residual_risk_score);

        $risk->appetite_status = $this->calculateAppetiteStatus($risk);

        $risk->save();

        return $risk;
    }

    /**
     * Batch update all risk scores
     */
    public function updateAllRiskScores(): int
    {
        $updated = 0;

        Risk::with(['riskControls.control', 'category'])->chunk(100, function ($risks) use (&$updated) {
            foreach ($risks as $risk) {
                $this->updateRiskScores($risk);
                $updated++;
            }
        });

        return $updated;
    }

    /**
     * Generate heatmap data (5x5 matrix)
     */
    public function generateHeatmap(string $type = 'inherent', ?int $categoryId = null): array
    {
        $query = Risk::query();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $risks = $query->get();

        // Initialize 5x5 matrix
        $matrix = [];
        for ($impact = 5; $impact >= 1; $impact--) {
            for ($probability = 1; $probability <= 5; $probability++) {
                $matrix["{$impact}_{$probability}"] = [
                    'impact' => $impact,
                    'probability' => $probability,
                    'score' => $impact * $probability,
                    'rag' => $this->getRAGFromScore($impact * $probability)->value,
                    'count' => 0,
                    'risks' => [],
                ];
            }
        }

        // Populate with risks
        foreach ($risks as $risk) {
            $impact = max(
                $risk->financial_impact ?? 0,
                $risk->regulatory_impact ?? 0,
                $risk->reputational_impact ?? 0
            );

            $probability = $type === 'inherent'
                ? ($risk->inherent_probability ?? 1)
                : $this->getResidualProbability($risk);

            $key = "{$impact}_{$probability}";

            if (isset($matrix[$key])) {
                $matrix[$key]['count']++;
                $matrix[$key]['risks'][] = [
                    'id' => $risk->id,
                    'ref_no' => $risk->ref_no,
                    'name' => $risk->name,
                ];
            }
        }

        return [
            'matrix' => array_values($matrix),
            'summary' => $this->getHeatmapSummary($risks, $type),
            'type' => $type,
        ];
    }

    /**
     * Get residual probability after controls
     */
    private function getResidualProbability(Risk $risk): int
    {
        $inherent = $risk->inherent_probability ?? 1;

        $controls = $risk->riskControls()
            ->where('is_active', true)
            ->count();

        // Each active control can reduce probability by 1 (min 1)
        return max(1, $inherent - min($controls, 2));
    }

    /**
     * Get heatmap summary statistics
     */
    private function getHeatmapSummary(Collection $risks, string $type): array
    {
        $scoreField = $type === 'inherent' ? 'inherent_risk_score' : 'residual_risk_score';
        $ragField = $type === 'inherent' ? 'inherent_rag' : 'residual_rag';

        return [
            'total_risks' => $risks->count(),
            'average_score' => round($risks->avg($scoreField) ?? 0, 1),
            'max_score' => $risks->max($scoreField) ?? 0,
            'by_rag' => [
                'green' => $risks->where($ragField, RAGStatus::GREEN)->count(),
                'amber' => $risks->where($ragField, RAGStatus::AMBER)->count(),
                'red' => $risks->where($ragField, RAGStatus::RED)->count(),
            ],
        ];
    }

    /**
     * Get risk trend analysis
     */
    public function getRiskTrend(int $riskId, int $months = 6): array
    {
        // This would typically query a risk_score_history table
        // For now, return current state
        $risk = Risk::find($riskId);

        if (!$risk) {
            return [];
        }

        return [
            'risk_id' => $riskId,
            'current_inherent' => $risk->inherent_risk_score,
            'current_residual' => $risk->residual_risk_score,
            'trend' => 'stable', // Would calculate from history
        ];
    }

    /**
     * Get category risk profile
     */
    public function getCategoryProfile(int $categoryId): array
    {
        $category = RiskCategory::with('risks')->find($categoryId);

        if (!$category) {
            return [];
        }

        $risks = $category->risks;

        return [
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'total_risks' => $risks->count(),
            'average_inherent' => round($risks->avg('inherent_risk_score') ?? 0, 1),
            'average_residual' => round($risks->avg('residual_risk_score') ?? 0, 1),
            'risk_reduction' => $this->calculateAverageReduction($risks),
            'appetite_status' => [
                'within' => $risks->where('appetite_status', RiskAppetiteStatus::WITHIN)->count(),
                'approaching' => $risks->where('appetite_status', RiskAppetiteStatus::APPROACHING)->count(),
                'exceeded' => $risks->where('appetite_status', RiskAppetiteStatus::EXCEEDED)->count(),
            ],
        ];
    }

    /**
     * Calculate average risk reduction from controls
     */
    private function calculateAverageReduction(Collection $risks): float
    {
        if ($risks->isEmpty()) {
            return 0;
        }

        $totalReduction = $risks->sum(function ($risk) {
            $inherent = $risk->inherent_risk_score ?? 0;
            $residual = $risk->residual_risk_score ?? 0;

            if ($inherent === 0) {
                return 0;
            }

            return (($inherent - $residual) / $inherent) * 100;
        });

        return round($totalReduction / $risks->count(), 1);
    }

    /**
     * Get impact scale labels
     */
    public function getImpactScale(): array
    {
        return self::IMPACT_SCALE;
    }

    /**
     * Get probability scale labels
     */
    public function getProbabilityScale(): array
    {
        return self::PROBABILITY_SCALE;
    }

    /**
     * Get RAG thresholds
     */
    public function getRAGThresholds(): array
    {
        return self::RAG_THRESHOLDS;
    }
}
