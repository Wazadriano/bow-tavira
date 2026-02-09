<?php

use App\Enums\RAGStatus;
use App\Models\Risk;
use App\Services\RiskScoringService;

beforeEach(function () {
    $this->service = new RiskScoringService();
});

// ============================================
// RG-BOW-003: Inherent score = max(3 impacts) x probability
// ============================================

it('calculates inherent score as max impact x probability', function () {
    $risk = new Risk([
        'financial_impact' => 3,
        'regulatory_impact' => 5,
        'reputational_impact' => 1,
        'inherent_probability' => 4,
    ]);

    expect($this->service->calculateInherentScore($risk))->toBe(20); // max(3,5,1) x 4 = 20
});

it('calculates max score of 25 with all impacts at 5 and probability 5', function () {
    $risk = new Risk([
        'financial_impact' => 5,
        'regulatory_impact' => 5,
        'reputational_impact' => 5,
        'inherent_probability' => 5,
    ]);

    expect($this->service->calculateInherentScore($risk))->toBe(25);
});

it('calculates min score of 1 with all impacts at 1 and probability 1', function () {
    $risk = new Risk([
        'financial_impact' => 1,
        'regulatory_impact' => 1,
        'reputational_impact' => 1,
        'inherent_probability' => 1,
    ]);

    expect($this->service->calculateInherentScore($risk))->toBe(1);
});

it('handles null impacts by defaulting to 0', function () {
    $risk = new Risk([
        'financial_impact' => null,
        'regulatory_impact' => 3,
        'reputational_impact' => null,
        'inherent_probability' => 2,
    ]);

    expect($this->service->calculateInherentScore($risk))->toBe(6); // max(0,3,0) x 2 = 6
});

it('handles null probability by defaulting to 1', function () {
    $risk = new Risk([
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 3,
        'inherent_probability' => null,
    ]);

    expect($this->service->calculateInherentScore($risk))->toBe(4); // max(4,2,3) x 1 = 4
});

// ============================================
// Risk RAG thresholds
// ============================================

it('returns GREEN for scores 1-4', function (int $score) {
    expect($this->service->getRAGFromScore($score))->toBe(RAGStatus::GREEN);
})->with([1, 2, 3, 4]);

it('returns AMBER for scores 5-12', function (int $score) {
    expect($this->service->getRAGFromScore($score))->toBe(RAGStatus::AMBER);
})->with([5, 6, 8, 10, 12]);

it('returns RED for scores 13-25', function (int $score) {
    expect($this->service->getRAGFromScore($score))->toBe(RAGStatus::RED);
})->with([13, 15, 20, 25]);

// ============================================
// Scale getters
// ============================================

it('returns impact scale with 5 levels', function () {
    expect($this->service->getImpactScale())->toHaveCount(5);
});

it('returns probability scale with 5 levels', function () {
    expect($this->service->getProbabilityScale())->toHaveCount(5);
});
