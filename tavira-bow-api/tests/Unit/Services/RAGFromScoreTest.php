<?php

use App\Services\RiskScoringService;
use App\Enums\RAGStatus;

beforeEach(function () {
    $this->service = new RiskScoringService();
});

// -------------------------------------------------------
// 1. Cas normaux
// -------------------------------------------------------

it('returns GREEN for a typical low risk score (2)', function () {
    expect($this->service->getRAGFromScore(2))->toBe(RAGStatus::GREEN);
});

it('returns AMBER for a typical medium risk score (8)', function () {
    expect($this->service->getRAGFromScore(8))->toBe(RAGStatus::AMBER);
});

it('returns RED for a typical high risk score (20)', function () {
    expect($this->service->getRAGFromScore(20))->toBe(RAGStatus::RED);
});

// -------------------------------------------------------
// 2. Bornes exactes
// -------------------------------------------------------

it('returns GREEN for score 4 (upper bound of green)', function () {
    expect($this->service->getRAGFromScore(4))->toBe(RAGStatus::GREEN);
});

it('returns AMBER for score 5 (lower bound of amber)', function () {
    expect($this->service->getRAGFromScore(5))->toBe(RAGStatus::AMBER);
});

it('returns AMBER for score 12 (upper bound of amber)', function () {
    expect($this->service->getRAGFromScore(12))->toBe(RAGStatus::AMBER);
});

it('returns RED for score 13 (lower bound of red)', function () {
    expect($this->service->getRAGFromScore(13))->toBe(RAGStatus::RED);
});

// -------------------------------------------------------
// 3. Extremes
// -------------------------------------------------------

it('returns GREEN for score 1 (minimum defined score)', function () {
    expect($this->service->getRAGFromScore(1))->toBe(RAGStatus::GREEN);
});

it('returns RED for score 25 (maximum defined score)', function () {
    expect($this->service->getRAGFromScore(25))->toBe(RAGStatus::RED);
});

// -------------------------------------------------------
// 4. Edge case : score 0
// -------------------------------------------------------

it('returns GREEN for score 0 (below minimum defined score)', function () {
    expect($this->service->getRAGFromScore(0))->toBe(RAGStatus::GREEN);
});
