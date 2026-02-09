<?php

use App\Enums\RAGStatus;
use App\Models\WorkItem;
use App\Services\RAGCalculationService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new RAGCalculationService();
});

// ============================================
// RG-BOW-001: RAG calculated automatically
// Blue = completed, Green = >14j, Amber = <7j, Red = overdue
// ============================================

it('returns BLUE for completed work items', function () {
    $item = new WorkItem(['deadline' => Carbon::now()->addDays(30)]);
    $item->current_status = new class {
        public string $value = 'completed';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::BLUE);
});

it('returns GREEN when no deadline is set', function () {
    $item = new WorkItem(['deadline' => null]);
    $item->current_status = new class {
        public string $value = 'in_progress';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::GREEN);
});

it('returns GREEN when deadline is more than 14 days away', function () {
    $item = new WorkItem(['deadline' => Carbon::now()->addDays(20)]);
    $item->current_status = new class {
        public string $value = 'in_progress';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::GREEN);
});

it('returns AMBER when deadline is within 7 days', function () {
    $item = new WorkItem(['deadline' => Carbon::now()->addDays(5)]);
    $item->current_status = new class {
        public string $value = 'in_progress';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::AMBER);
});

it('returns AMBER when not started and deadline within 14 days', function () {
    $item = new WorkItem(['deadline' => Carbon::now()->addDays(10)]);
    $item->current_status = new class {
        public string $value = 'not_started';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::AMBER);
});

it('returns RED when deadline is past', function () {
    $item = new WorkItem(['deadline' => Carbon::now()->subDays(1)]);
    $item->current_status = new class {
        public string $value = 'in_progress';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::RED);
});

// ============================================
// Edge cases: exact boundaries
// ============================================

it('returns AMBER when deadline is exactly 7 days away', function () {
    $item = new WorkItem(['deadline' => Carbon::now()->addDays(7)]);
    $item->current_status = new class {
        public string $value = 'in_progress';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::AMBER);
});

it('returns BLUE regardless of deadline when completed', function () {
    $item = new WorkItem(['deadline' => Carbon::now()->subDays(30)]);
    $item->current_status = new class {
        public string $value = 'completed';
    };

    expect($this->service->calculateWorkItemRAG($item))->toBe(RAGStatus::BLUE);
});
