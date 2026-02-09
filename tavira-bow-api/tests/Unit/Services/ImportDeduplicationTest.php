<?php

use App\Services\ImportNormalizationService;

beforeEach(function () {
    $this->service = new ImportNormalizationService();
});

// ============================================================
// TDD: RG-BOW-008 — Déduplication à l'import
// Ces tests DOIVENT échouer car la méthode detectDuplicates()
// n'existe pas encore. C'est le principe du TDD :
// 1. RED   — écrire le test, il échoue
// 2. GREEN — implémenter le code pour le faire passer
// 3. REFACTOR — nettoyer
// ============================================================

it('detects exact duplicate rows in import data', function () {
    $this->markTestSkipped('TDD RED: detectDuplicates() not implemented yet (RG-BOW-008)');

    $rows = [
        ['ref_no' => 'WI-001', 'name' => 'Task Alpha', 'department' => 'IT'],
        ['ref_no' => 'WI-002', 'name' => 'Task Beta', 'department' => 'Finance'],
        ['ref_no' => 'WI-001', 'name' => 'Task Alpha', 'department' => 'IT'],
    ];

    $duplicates = $this->service->detectDuplicates($rows, 'ref_no');

    expect($duplicates)->toHaveCount(1);
    expect($duplicates[0]['ref_no'])->toBe('WI-001');
});

it('returns empty array when no duplicates exist', function () {
    $this->markTestSkipped('TDD RED: detectDuplicates() not implemented yet (RG-BOW-008)');

    $rows = [
        ['ref_no' => 'WI-001', 'name' => 'Task Alpha'],
        ['ref_no' => 'WI-002', 'name' => 'Task Beta'],
        ['ref_no' => 'WI-003', 'name' => 'Task Gamma'],
    ];

    $duplicates = $this->service->detectDuplicates($rows, 'ref_no');

    expect($duplicates)->toBeEmpty();
});

it('detects fuzzy duplicates with similar names', function () {
    $this->markTestSkipped('TDD RED: detectDuplicates() not implemented yet (RG-BOW-008)');

    $rows = [
        ['ref_no' => 'WI-001', 'name' => 'John Smith'],
        ['ref_no' => 'WI-002', 'name' => 'J. Smith'],
        ['ref_no' => 'WI-003', 'name' => 'Jane Doe'],
    ];

    $duplicates = $this->service->detectDuplicates($rows, 'name', fuzzy: true);

    expect($duplicates)->not->toBeEmpty();
});
