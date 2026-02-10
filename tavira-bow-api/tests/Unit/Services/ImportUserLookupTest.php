<?php

use App\Models\User;
use App\Services\ImportNormalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ImportNormalizationService;
});

// ============================================================
// TDD: RG-BOW-??? — User Lookup Resolution
// Tests for resolveUserId() method with caching and fuzzy matching
// ============================================================

// Test 1: null input returns null
it('returns null when input is null', function () {
    $result = $this->service->resolveUserId(null);

    expect($result)->toBeNull();
});

// Test 2: empty string returns null
it('returns null when input is empty string', function () {
    $result = $this->service->resolveUserId('');

    expect($result)->toBeNull();
});

// Test 3: whitespace-only string returns null
it('returns null when input is whitespace only', function () {
    $result = $this->service->resolveUserId('   ');

    expect($result)->toBeNull();
});

// Test 4: exact full_name match returns user id
it('returns user id for exact full_name match', function () {
    $user = User::factory()->create([
        'full_name' => 'John Smith',
        'email' => 'john.smith@example.com',
    ]);

    $result = $this->service->resolveUserId('John Smith');

    expect($result)->toBe($user->id);
});

// Test 5: exact email match returns user id
it('returns user id for exact email match', function () {
    $user = User::factory()->create([
        'full_name' => 'Jane Doe',
        'email' => 'jane.doe@example.com',
    ]);

    $result = $this->service->resolveUserId('jane.doe@example.com');

    expect($result)->toBe($user->id);
});

// Test 6: fuzzy partial name match (substring) returns user id
it('returns user id for fuzzy ILIKE partial name match', function () {
    $user = User::factory()->create([
        'full_name' => 'John Smith',
        'email' => 'john@example.com',
    ]);

    $result = $this->service->resolveUserId('John');

    expect($result)->toBe($user->id);
});

// Test 7: fuzzy partial name match (middle of name) returns user id
it('returns user id for fuzzy ILIKE match in middle of name', function () {
    $user = User::factory()->create([
        'full_name' => 'John Smith',
        'email' => 'john@example.com',
    ]);

    $result = $this->service->resolveUserId('Smith');

    expect($result)->toBe($user->id);
});

// Test 8: fuzzy match is case-insensitive
it('performs case-insensitive fuzzy matching', function () {
    $user = User::factory()->create([
        'full_name' => 'John Smith',
        'email' => 'john@example.com',
    ]);

    $result = $this->service->resolveUserId('JOHN');

    expect($result)->toBe($user->id);
});

// Test 9: non-existent user returns null and adds warning
it('returns null and adds warning for non-existent user', function () {
    $result = $this->service->resolveUserId('NonExistentUser');

    expect($result)->toBeNull();
    expect($this->service->getWarnings())->toContain("User not found: 'NonExistentUser'");
});

// Test 10: warning is not added when user is found
it('does not add warning when user is found', function () {
    User::factory()->create([
        'full_name' => 'Alice Brown',
        'email' => 'alice@example.com',
    ]);

    $this->service->resolveUserId('Alice Brown');

    expect($this->service->getWarnings())->not->toContain("User not found: 'Alice Brown'");
});

// Test 11: cached results are consistent (same input returns same result)
it('returns consistent cached results on repeated lookups', function () {
    $user = User::factory()->create([
        'full_name' => 'Bob Johnson',
        'email' => 'bob@example.com',
    ]);

    $result1 = $this->service->resolveUserId('Bob Johnson');
    $result2 = $this->service->resolveUserId('Bob Johnson');

    expect($result1)->toBe($user->id);
    expect($result2)->toBe($user->id);
    expect($result1)->toBe($result2);
});

// Test 12: cache key is case-insensitive (both lookups hit cache)
it('uses case-insensitive cache keys', function () {
    $user = User::factory()->create([
        'full_name' => 'Carol White',
        'email' => 'carol@example.com',
    ]);

    $result1 = $this->service->resolveUserId('Carol White');
    $result2 = $this->service->resolveUserId('carol white');
    $result3 = $this->service->resolveUserId('CAROL WHITE');

    expect($result1)->toBe($user->id);
    expect($result2)->toBe($user->id);
    expect($result3)->toBe($user->id);
});

// Test 13: exact match takes precedence over fuzzy match
it('prefers exact full_name match over fuzzy match', function () {
    // Create two users where one's name contains part of the other
    $exactUser = User::factory()->create([
        'full_name' => 'John',
        'email' => 'john@example.com',
    ]);

    User::factory()->create([
        'full_name' => 'John Smith',
        'email' => 'johnsmith@example.com',
    ]);

    $result = $this->service->resolveUserId('John');

    // Should match the exact name 'John', not fuzzy match to 'John Smith'
    expect($result)->toBe($exactUser->id);
});

// Test 14: email match takes precedence over fuzzy match
it('prefers exact email match over fuzzy full_name match', function () {
    $emailUser = User::factory()->create([
        'full_name' => 'Alice Anderson',
        'email' => 'alice@example.com',
    ]);

    User::factory()->create([
        'full_name' => 'Alice Alice',
        'email' => 'other@example.com',
    ]);

    $result = $this->service->resolveUserId('alice@example.com');

    expect($result)->toBe($emailUser->id);
});

// Test 15: trimmed input is used for lookup
it('trims whitespace from input before lookup', function () {
    $user = User::factory()->create([
        'full_name' => 'David Davis',
        'email' => 'david@example.com',
    ]);

    $result = $this->service->resolveUserId('  David Davis  ');

    expect($result)->toBe($user->id);
});

// Test 16: multiple warnings accumulate
it('accumulates multiple warnings for multiple lookups', function () {
    $this->service->resolveUserId('NonExistent1');
    $this->service->resolveUserId('NonExistent2');

    $warnings = $this->service->getWarnings();

    expect($warnings)->toContain("User not found: 'NonExistent1'");
    expect($warnings)->toContain("User not found: 'NonExistent2'");
    expect(count($warnings))->toBe(2);
});

// Test 17: cached null result is also cached
it('caches null results for non-existent users', function () {
    $result1 = $this->service->resolveUserId('NonExistentUser');
    $warnings1Count = count($this->service->getWarnings());

    $result2 = $this->service->resolveUserId('NonExistentUser');
    $warnings2Count = count($this->service->getWarnings());

    expect($result1)->toBeNull();
    expect($result2)->toBeNull();
    // Should not add warning again (used cache)
    expect($warnings2Count)->toBe($warnings1Count);
});
