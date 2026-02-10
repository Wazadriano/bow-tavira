<?php

use App\Services\ImportNormalizationService;

beforeEach(function () {
    $this->service = new ImportNormalizationService;
});

// 1. null and empty values
it('returns null when value is null', function () {
    expect($this->service->parseDate(null))->toBeNull();
});

it('returns null when value is empty string', function () {
    expect($this->service->parseDate(''))->toBeNull();
});

// 2. Standard formats (Y-m-d, d/m/Y, m/d/Y, d-m-Y, Y/m/d)
it('parses Y-m-d format correctly', function () {
    expect($this->service->parseDate('2025-01-15'))->toBe('2025-01-15');
});

it('parses d/m/Y format correctly', function () {
    expect($this->service->parseDate('15/01/2025'))->toBe('2025-01-15');
});

it('parses m/d/Y format correctly', function () {
    expect($this->service->parseDate('01/15/2025'))->toBe('2025-01-15');
});

it('parses d dash m dash Y format correctly', function () {
    expect($this->service->parseDate('15-01-2025'))->toBe('2025-01-15');
});

it('parses Y slash m slash d format correctly', function () {
    expect($this->service->parseDate('2025/01/15'))->toBe('2025-01-15');
});

// 3. "Mon YYYY" formats - short month format (M Y)
it('parses Jul 2026 short month format to 1st of month', function () {
    expect($this->service->parseDate('Jul 2026'))->toBe('2026-07-01');
});

it('parses Dec 2025 short month format to 1st of month', function () {
    expect($this->service->parseDate('Dec 2025'))->toBe('2025-12-01');
});

it('parses Jan 2026 short month format to 1st of month', function () {
    expect($this->service->parseDate('Jan 2026'))->toBe('2026-01-01');
});

// 4. "Mon YYYY" formats - full month format (F Y)
it('parses July 2026 full month format to 1st of month', function () {
    expect($this->service->parseDate('July 2026'))->toBe('2026-07-01');
});

it('parses December 2025 full month format to 1st of month', function () {
    expect($this->service->parseDate('December 2025'))->toBe('2025-12-01');
});

it('parses January 2026 full month format to 1st of month', function () {
    expect($this->service->parseDate('January 2026'))->toBe('2026-01-01');
});

// 5. "Mon YY" formats - short month and 2-digit year (M y)
it('parses Jan 26 short month and 2-digit year format to 1st of month', function () {
    expect($this->service->parseDate('Jan 26'))->toBe('2026-01-01');
});

it('parses Dec 25 short month and 2-digit year format to 1st of month', function () {
    expect($this->service->parseDate('Dec 25'))->toBe('2025-12-01');
});

// 6. Excel serial dates
it('parses Excel serial date 46022 to valid date', function () {
    $result = $this->service->parseDate(46022);
    expect($result)->not->toBeNull();
    // Excel serial 46022 should be approximately 2026-01-01
    expect($result)->toMatch('/^202[0-9]-\d{2}-\d{2}$/');
});

it('parses Excel serial date as float', function () {
    $result = $this->service->parseDate(45292.0);
    expect($result)->not->toBeNull();
    expect($result)->toMatch('/^202[0-9]-\d{2}-\d{2}$/');
});

// 7. strtotime fallback
it('parses strtotime format "15 January 2025"', function () {
    expect($this->service->parseDate('15 January 2025'))->toBe('2025-01-15');
});

it('parses strtotime format "January 15, 2025"', function () {
    expect($this->service->parseDate('January 15, 2025'))->toBe('2025-01-15');
});

it('parses strtotime format "01-15-2025"', function () {
    expect($this->service->parseDate('01-15-2025'))->toBe('2025-01-15');
});

// 8. Invalid dates
it('returns null for invalid date string "not-a-date"', function () {
    expect($this->service->parseDate('not-a-date'))->toBeNull();
});

it('returns null for invalid date string "32/01/2025"', function () {
    expect($this->service->parseDate('32/01/2025'))->toBeNull();
});

it('returns null for invalid date string "2025-13-01"', function () {
    expect($this->service->parseDate('2025-13-01'))->toBeNull();
});

it('returns null for invalid date string "abcd"', function () {
    expect($this->service->parseDate('abcd'))->toBeNull();
});

// 9. Edge cases
it('parses February 29 2024 leap year correctly', function () {
    expect($this->service->parseDate('2024-02-29'))->toBe('2024-02-29');
});

it('returns null for February 29 2025 non-leap year', function () {
    expect($this->service->parseDate('2025-02-29'))->toBeNull();
});

it('parses single digit day and month with leading zeros', function () {
    expect($this->service->parseDate('2025-01-05'))->toBe('2025-01-05');
});

it('returns null for numeric value less than 25569', function () {
    expect($this->service->parseDate(25000))->toBeNull();
});
