<?php

use App\Enums\CurrentStatus;
use App\Enums\RAGStatus;
use App\Models\GovernanceItem;
use App\Services\RAGCalculationService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new RAGCalculationService;
});

// ============================================
// RG-GOV-001: Governance RAG calculated automatically
// Blue = completed, Green = >7j or no deadline, Amber = <=7j, Red = overdue
// ============================================

it('returns BLUE for completed governance item', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::COMPLETED, 'deadline' => null]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::BLUE);
});

it('returns BLUE for completed governance item even with past deadline', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::COMPLETED, 'deadline' => Carbon::now()->subDays(30)]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::BLUE);
});

it('returns GREEN when no deadline is set', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::IN_PROGRESS, 'deadline' => null]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::GREEN);
});

it('returns GREEN when deadline is 20 days away', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::IN_PROGRESS, 'deadline' => Carbon::now()->addDays(20)]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::GREEN);
});

it('returns AMBER when deadline is 5 days away', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::IN_PROGRESS, 'deadline' => Carbon::now()->addDays(5)]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::AMBER);
});

it('returns AMBER when deadline is exactly 7 days away', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::IN_PROGRESS, 'deadline' => Carbon::now()->addDays(7)]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::AMBER);
});

it('returns RED when deadline is past (yesterday)', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::IN_PROGRESS, 'deadline' => Carbon::now()->subDays(1)]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::RED);
});

it('returns GREEN when deadline is 8 days away (just above amber boundary)', function () {
    $item = new GovernanceItem(['current_status' => CurrentStatus::IN_PROGRESS, 'deadline' => Carbon::now()->addDays(8)]);

    expect($this->service->calculateGovernanceRAG($item))->toBe(RAGStatus::GREEN);
});
