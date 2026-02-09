<?php

use App\Services\ImportNormalizationService;

beforeEach(function () {
    $this->service = new ImportNormalizationService();
});

// 1. null
it('returns null when value is null for string type', function () {
    expect($this->service->transformValue(null, 'string'))->toBeNull();
});

it('returns null when value is null for int type', function () {
    expect($this->service->transformValue(null, 'int'))->toBeNull();
});

// 2. string
it('casts integer to string', function () {
    expect($this->service->transformValue(123, 'string'))->toBe('123');
});

// 3. integer
it('casts string to integer with type integer', function () {
    expect($this->service->transformValue('42', 'integer'))->toBe(42);
});

it('casts string to integer with type int', function () {
    expect($this->service->transformValue('42', 'int'))->toBe(42);
});

// 4. float with European comma
it('converts European comma float with type float', function () {
    expect($this->service->transformValue('3,14', 'float'))->toBe(3.14);
});

it('converts European comma float with type decimal', function () {
    expect($this->service->transformValue('3,14', 'decimal'))->toBe(3.14);
});

// 5. float with dot
it('converts dot float with type float', function () {
    expect($this->service->transformValue('3.14', 'float'))->toBe(3.14);
});

// 6. boolean
it('converts yes to true with type bool', function () {
    expect($this->service->transformValue('yes', 'bool'))->toBeTrue();
});

it('converts true string to true with type boolean', function () {
    expect($this->service->transformValue('true', 'boolean'))->toBeTrue();
});

it('converts no to false with type bool', function () {
    expect($this->service->transformValue('no', 'bool'))->toBeFalse();
});

it('converts 0 to false with type bool', function () {
    expect($this->service->transformValue('0', 'bool'))->toBeFalse();
});

// 7. date
it('parses natural date string to Y-m-d', function () {
    expect($this->service->transformValue('15 January 2025', 'date'))->toBe('2025-01-15');
});

it('parses Y-m-d date string to Y-m-d', function () {
    expect($this->service->transformValue('2025-01-15', 'date'))->toBe('2025-01-15');
});

// 8. invalid date
it('returns null for invalid date', function () {
    expect($this->service->transformValue('not-a-date', 'date'))->toBeNull();
});

// 9. json
it('decodes JSON array string with type json', function () {
    expect($this->service->transformValue('["a","b"]', 'json'))->toBe(['a', 'b']);
});

// 10. unknown type
it('returns value as-is for unknown type', function () {
    expect($this->service->transformValue('hello', 'unknown'))->toBe('hello');
});
