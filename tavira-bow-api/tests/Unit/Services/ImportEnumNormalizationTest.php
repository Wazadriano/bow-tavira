<?php

use App\Enums\BAUType;
use App\Enums\CurrentStatus;
use App\Enums\ImpactLevel;
use App\Enums\RAGStatus;
use App\Enums\UpdateFrequency;
use App\Services\ImportNormalizationService;

beforeEach(function () {
    $this->service = new ImportNormalizationService;
});

// Null and empty values
it('returns null for null value', function () {
    expect($this->service->normalizeEnumValue(null, BAUType::class))->toBeNull();
});

it('returns null for empty string', function () {
    expect($this->service->normalizeEnumValue('', CurrentStatus::class))->toBeNull();
});

// BAUType
it('normalizes BAUType exact match', function () {
    expect($this->service->normalizeEnumValue('BAU', BAUType::class))->toBe('BAU');
    expect($this->service->normalizeEnumValue('Non BAU', BAUType::class))->toBe('Non BAU');
});

it('normalizes BAUType case-insensitive', function () {
    expect($this->service->normalizeEnumValue('bau', BAUType::class))->toBe('BAU');
    expect($this->service->normalizeEnumValue('non bau', BAUType::class))->toBe('Non BAU');
});

it('normalizes BAUType aliases', function () {
    expect($this->service->normalizeEnumValue('growth', BAUType::class))->toBe('Non BAU');
    expect($this->service->normalizeEnumValue('transformative', BAUType::class))->toBe('Non BAU');
    expect($this->service->normalizeEnumValue('non-bau', BAUType::class))->toBe('Non BAU');
    expect($this->service->normalizeEnumValue('Business As Usual', BAUType::class))->toBe('BAU');
});

// CurrentStatus
it('normalizes CurrentStatus exact match', function () {
    expect($this->service->normalizeEnumValue('Not Started', CurrentStatus::class))->toBe('Not Started');
    expect($this->service->normalizeEnumValue('In Progress', CurrentStatus::class))->toBe('In Progress');
    expect($this->service->normalizeEnumValue('Completed', CurrentStatus::class))->toBe('Completed');
    expect($this->service->normalizeEnumValue('On Hold', CurrentStatus::class))->toBe('On Hold');
});

it('normalizes CurrentStatus aliases', function () {
    expect($this->service->normalizeEnumValue('not started', CurrentStatus::class))->toBe('Not Started');
    expect($this->service->normalizeEnumValue('not_started', CurrentStatus::class))->toBe('Not Started');
    expect($this->service->normalizeEnumValue('in progress', CurrentStatus::class))->toBe('In Progress');
    expect($this->service->normalizeEnumValue('in_progress', CurrentStatus::class))->toBe('In Progress');
    expect($this->service->normalizeEnumValue('done', CurrentStatus::class))->toBe('Completed');
    expect($this->service->normalizeEnumValue('pending', CurrentStatus::class))->toBe('On Hold');
});

// ImpactLevel
it('normalizes ImpactLevel exact match', function () {
    expect($this->service->normalizeEnumValue('High', ImpactLevel::class))->toBe('High');
    expect($this->service->normalizeEnumValue('Medium', ImpactLevel::class))->toBe('Medium');
    expect($this->service->normalizeEnumValue('Low', ImpactLevel::class))->toBe('Low');
});

it('normalizes ImpactLevel numeric aliases', function () {
    expect($this->service->normalizeEnumValue('3', ImpactLevel::class))->toBe('High');
    expect($this->service->normalizeEnumValue('2', ImpactLevel::class))->toBe('Medium');
    expect($this->service->normalizeEnumValue('1', ImpactLevel::class))->toBe('Low');
});

it('normalizes ImpactLevel letter aliases', function () {
    expect($this->service->normalizeEnumValue('h', ImpactLevel::class))->toBe('High');
    expect($this->service->normalizeEnumValue('m', ImpactLevel::class))->toBe('Medium');
    expect($this->service->normalizeEnumValue('l', ImpactLevel::class))->toBe('Low');
});

// RAGStatus
it('normalizes RAGStatus exact match', function () {
    expect($this->service->normalizeEnumValue('Blue', RAGStatus::class))->toBe('Blue');
    expect($this->service->normalizeEnumValue('Green', RAGStatus::class))->toBe('Green');
    expect($this->service->normalizeEnumValue('Amber', RAGStatus::class))->toBe('Amber');
    expect($this->service->normalizeEnumValue('Red', RAGStatus::class))->toBe('Red');
});

it('normalizes RAGStatus aliases', function () {
    expect($this->service->normalizeEnumValue('b', RAGStatus::class))->toBe('Blue');
    expect($this->service->normalizeEnumValue('g', RAGStatus::class))->toBe('Green');
    expect($this->service->normalizeEnumValue('a', RAGStatus::class))->toBe('Amber');
    expect($this->service->normalizeEnumValue('orange', RAGStatus::class))->toBe('Amber');
    expect($this->service->normalizeEnumValue('r', RAGStatus::class))->toBe('Red');
});

// UpdateFrequency
it('normalizes UpdateFrequency exact match', function () {
    expect($this->service->normalizeEnumValue('Annually', UpdateFrequency::class))->toBe('Annually');
    expect($this->service->normalizeEnumValue('Monthly', UpdateFrequency::class))->toBe('Monthly');
    expect($this->service->normalizeEnumValue('Weekly', UpdateFrequency::class))->toBe('Weekly');
});

it('normalizes UpdateFrequency aliases', function () {
    expect($this->service->normalizeEnumValue('annual', UpdateFrequency::class))->toBe('Annually');
    expect($this->service->normalizeEnumValue('yearly', UpdateFrequency::class))->toBe('Annually');
    expect($this->service->normalizeEnumValue('bi-annual', UpdateFrequency::class))->toBe('Semi Annually');
    expect($this->service->normalizeEnumValue('quarter', UpdateFrequency::class))->toBe('Quarterly');
    expect($this->service->normalizeEnumValue('month', UpdateFrequency::class))->toBe('Monthly');
    expect($this->service->normalizeEnumValue('week', UpdateFrequency::class))->toBe('Weekly');
});

// Unknown value returns null with warning
it('returns null for unknown enum value', function () {
    expect($this->service->normalizeEnumValue('invalid_value', BAUType::class))->toBeNull();
    $warnings = $this->service->getWarnings();
    expect($warnings)->toContain("Unknown enum value 'invalid_value' for ".BAUType::class);
});

// Whitespace handling
it('trims whitespace from values', function () {
    expect($this->service->normalizeEnumValue('  BAU  ', BAUType::class))->toBe('BAU');
    expect($this->service->normalizeEnumValue(' High ', ImpactLevel::class))->toBe('High');
});
