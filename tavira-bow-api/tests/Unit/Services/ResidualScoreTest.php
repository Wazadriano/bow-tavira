<?php

use Mockery;
use App\Models\Risk;
use App\Services\RiskScoringService;

beforeEach(function () {
    $this->service = new RiskScoringService();
});

afterEach(function () {
    Mockery::close();
});

// Helper pour creer un Risk mocke avec des controles
function createRiskWithControls(array $attributes, array $controlEffectiveness = []): Risk
{
    $risk = Mockery::mock(Risk::class)->makePartial();
    $risk->financial_impact = $attributes['financial_impact'] ?? 0;
    $risk->regulatory_impact = $attributes['regulatory_impact'] ?? 0;
    $risk->reputational_impact = $attributes['reputational_impact'] ?? 0;
    $risk->inherent_probability = $attributes['inherent_probability'] ?? 1;

    // Creer les controles mockes
    $controls = collect(array_map(function ($eff) {
        $control = new \stdClass();
        $control->effectiveness = (object) ['value' => $eff];
        return $control;
    }, $controlEffectiveness));

    // Mock la chaine riskControls()->with()->where()->get()
    $queryMock = Mockery::mock();
    $queryMock->shouldReceive('with')->andReturnSelf();
    $queryMock->shouldReceive('where')->andReturnSelf();
    $queryMock->shouldReceive('get')->andReturn($controls);
    $risk->shouldReceive('riskControls')->andReturn($queryMock);

    return $risk;
}

// ============================================
// RG-BOW-004: Residual score = inherent * (1 - reduction)
// Plafond de reduction a 70%
// ============================================

it('returns inherent score when no controls exist', function () {
    $risk = createRiskWithControls([
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 3,
        'inherent_probability' => 5,
    ], []);

    // inherent = max(4,2,3) * 5 = 20, pas de controle => residual = 20
    expect($this->service->calculateResidualScore($risk))->toBe(20);
});

it('reduces score by 30% with one effective control', function () {
    $risk = createRiskWithControls([
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 3,
        'inherent_probability' => 5,
    ], ['effective']);

    // inherent = 20, reduction = 0.3 => residual = round(20 * 0.7) = 14
    expect($this->service->calculateResidualScore($risk))->toBe(14);
});

it('reduces score by 15% with one partially effective control', function () {
    $risk = createRiskWithControls([
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 3,
        'inherent_probability' => 5,
    ], ['partially_effective']);

    // inherent = 20, reduction = 0.15 => residual = round(20 * 0.85) = 17
    expect($this->service->calculateResidualScore($risk))->toBe(17);
});

it('does not reduce score with an ineffective control', function () {
    $risk = createRiskWithControls([
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 3,
        'inherent_probability' => 5,
    ], ['ineffective']);

    // inherent = 20, reduction = 0 => residual = 20
    expect($this->service->calculateResidualScore($risk))->toBe(20);
});

it('reduces score by 60% with two effective controls', function () {
    $risk = createRiskWithControls([
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 3,
        'inherent_probability' => 5,
    ], ['effective', 'effective']);

    // inherent = 20, reduction = 0.6 => residual = round(20 * 0.4) = 8
    expect($this->service->calculateResidualScore($risk))->toBe(8);
});

it('caps reduction at 70% even with three effective controls (90% total)', function () {
    $risk = createRiskWithControls([
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 3,
        'inherent_probability' => 5,
    ], ['effective', 'effective', 'effective']);

    // inherent = 20, reduction = min(0.9, 0.7) = 0.7 => residual = round(20 * 0.3) = 6
    expect($this->service->calculateResidualScore($risk))->toBe(6);
});

it('enforces minimum residual score of 1', function () {
    $risk = createRiskWithControls([
        'financial_impact' => 1,
        'regulatory_impact' => 0,
        'reputational_impact' => 0,
        'inherent_probability' => 1,
    ], ['effective']);

    // inherent = 1, reduction = 0.3 => round(1 * 0.7) = 1, max(1, 1) = 1
    expect($this->service->calculateResidualScore($risk))->toBe(1);
});
