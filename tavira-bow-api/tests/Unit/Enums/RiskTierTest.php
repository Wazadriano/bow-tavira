<?php

use App\Enums\RiskTier;

// ============================================================
// 1. Cas normaux
// ============================================================

it('returns TIER_A for a high score (15)', function () {
    expect(RiskTier::fromScore(15))->toBe(RiskTier::TIER_A);
});

it('returns TIER_B for a medium score (6)', function () {
    expect(RiskTier::fromScore(6))->toBe(RiskTier::TIER_B);
});

it('returns TIER_C for a low score (2)', function () {
    expect(RiskTier::fromScore(2))->toBe(RiskTier::TIER_C);
});

// ============================================================
// 2. Bornes exactes
// ============================================================

it('returns TIER_A at the exact lower bound (score 9)', function () {
    expect(RiskTier::fromScore(9))->toBe(RiskTier::TIER_A);
});

it('returns TIER_B just below TIER_A threshold (score 8)', function () {
    expect(RiskTier::fromScore(8))->toBe(RiskTier::TIER_B);
});

it('returns TIER_B at the exact lower bound (score 4)', function () {
    expect(RiskTier::fromScore(4))->toBe(RiskTier::TIER_B);
});

it('returns TIER_C just below TIER_B threshold (score 3)', function () {
    expect(RiskTier::fromScore(3))->toBe(RiskTier::TIER_C);
});

// ============================================================
// 3. Valeurs extremes
// ============================================================

it('returns TIER_A for a very high score (25)', function () {
    expect(RiskTier::fromScore(25))->toBe(RiskTier::TIER_A);
});

it('returns TIER_C for a very low score (1)', function () {
    expect(RiskTier::fromScore(1))->toBe(RiskTier::TIER_C);
});

// ============================================================
// 4. Score zero
// ============================================================

it('returns TIER_C for score 0', function () {
    expect(RiskTier::fromScore(0))->toBe(RiskTier::TIER_C);
});
