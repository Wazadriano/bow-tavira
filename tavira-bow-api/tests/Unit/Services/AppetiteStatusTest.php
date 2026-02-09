<?php

use App\Models\Risk;
use App\Enums\RiskAppetiteStatus;
use App\Services\RiskScoringService;

beforeEach(function () {
    $this->service = new RiskScoringService;
});

afterEach(function () {
    Mockery::close();
});

// ============================================
// Helper : create a mocked Risk with controlled residual score and appetite threshold
// ============================================

function createRiskForAppetite(int $residualScore, ?int $appetiteThreshold = null): Risk
{
    // On cree un risk dont le score inherent = residual voulu (pas de controles)
    // Pour ca on met financial_impact = residualScore, probability = 1, pas de controles
    $risk = Mockery::mock(Risk::class)->makePartial();
    $risk->financial_impact = $residualScore;
    $risk->regulatory_impact = 0;
    $risk->reputational_impact = 0;
    $risk->inherent_probability = 1;

    // Mock controles vides (pas de reduction)
    $queryMock = Mockery::mock();
    $queryMock->shouldReceive('with')->andReturnSelf();
    $queryMock->shouldReceive('where')->andReturnSelf();
    $queryMock->shouldReceive('get')->andReturn(collect([]));
    $risk->shouldReceive('riskControls')->andReturn($queryMock);

    // Mock la categorie avec le seuil d'appetence
    if ($appetiteThreshold !== null) {
        $category = (object) ['risk_appetite_threshold' => $appetiteThreshold];
        $risk->category = $category;
    } else {
        $risk->category = null; // Utilisera le defaut (8)
    }

    return $risk;
}

// ============================================
// RG-BOW-005: Appetite status = f(residual score, appetite threshold)
// ============================================

it('returns WITHIN when residual score is below threshold', function () {
    // score 5, seuil 8 -> WITHIN (5 <= 8)
    $risk = createRiskForAppetite(5, 8);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::WITHIN);
});

it('returns WITHIN when residual score equals threshold exactly', function () {
    // score 8, seuil 8 -> WITHIN (8 <= 8)
    $risk = createRiskForAppetite(8, 8);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::WITHIN);
});

it('returns APPROACHING when residual score is above threshold but within 1.5x', function () {
    // score 9, seuil 8 -> APPROACHING (9 > 8 mais 9 <= 12)
    $risk = createRiskForAppetite(9, 8);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::APPROACHING);
});

it('returns APPROACHING when residual score equals 1.5x threshold exactly', function () {
    // score 12, seuil 8 -> APPROACHING (12 <= 8 * 1.5 = 12)
    $risk = createRiskForAppetite(12, 8);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::APPROACHING);
});

it('returns EXCEEDED when residual score is above 1.5x threshold', function () {
    // score 13, seuil 8 -> EXCEEDED (13 > 12)
    $risk = createRiskForAppetite(13, 8);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::EXCEEDED);
});

it('returns EXCEEDED for very high score with low threshold', function () {
    // score 25, seuil 2 -> EXCEEDED (25 > 2 * 1.5 = 3)
    $risk = createRiskForAppetite(25, 2);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::EXCEEDED);
});

it('uses default threshold of 8 when category is null and returns WITHIN', function () {
    // score 7, categorie null -> WITHIN (defaut seuil=8, 7 <= 8)
    $risk = createRiskForAppetite(7, null);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::WITHIN);
});

it('uses default threshold of 8 when category is null and returns EXCEEDED', function () {
    // score 15, categorie null -> EXCEEDED (defaut seuil=8, 15 > 12)
    $risk = createRiskForAppetite(15, null);

    expect($this->service->calculateAppetiteStatus($risk))
        ->toBe(RiskAppetiteStatus::EXCEEDED);
});
