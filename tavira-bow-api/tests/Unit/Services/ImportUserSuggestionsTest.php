<?php

use App\Models\User;
use App\Services\ImportNormalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ImportNormalizationService;
});

it('returns exact match for full name', function () {
    $uniqueName = 'Zephyrion Uniquename'.mt_rand(10000, 99999);
    $user = User::factory()->create(['full_name' => $uniqueName]);

    $suggestions = $this->service->suggestUsers($uniqueName);

    expect($suggestions)->toHaveCount(1);
    expect($suggestions[0]['user_id'])->toBe($user->id);
    expect($suggestions[0]['confidence'])->toBe(100);
    expect($suggestions[0]['match_type'])->toBe('exact');
});

it('returns exact match for email', function () {
    $user = User::factory()->create(['email' => 'zephyr.unique.test@test.com']);

    $suggestions = $this->service->suggestUsers('zephyr.unique.test@test.com');

    expect($suggestions)->toHaveCount(1);
    expect($suggestions[0]['user_id'])->toBe($user->id);
    expect($suggestions[0]['confidence'])->toBe(100);
});

it('expands initials to match full name', function () {
    $user = User::factory()->create(['full_name' => 'Xavion Quilford']);

    $suggestions = $this->service->suggestUsers('X.Quilford');

    expect($suggestions)->not->toBeEmpty();
    $found = collect($suggestions)->firstWhere('user_id', $user->id);
    expect($found)->not->toBeNull();
    expect($found['match_type'])->toBe('initial_expansion');
});

it('expands initials with space', function () {
    $user = User::factory()->create(['full_name' => 'Yorick Pembleton']);

    $suggestions = $this->service->suggestUsers('Y Pembleton');

    expect($suggestions)->not->toBeEmpty();
    $found = collect($suggestions)->firstWhere('user_id', $user->id);
    expect($found)->not->toBeNull();
});

it('resolves known alias to correct user', function () {
    $user = User::factory()->create(['full_name' => 'Rebecca Reffell']);

    $suggestions = $this->service->suggestUsers('Rebecca Refell');

    expect($suggestions)->toHaveCount(1);
    expect($suggestions[0]['full_name'])->toBe('Rebecca Reffell');
    expect($suggestions[0]['match_type'])->toBe('alias');
});

it('returns substring matches', function () {
    $user = User::factory()->create(['full_name' => 'Ylva Bergstrom']);

    $suggestions = $this->service->suggestUsers('Bergstrom');

    expect($suggestions)->not->toBeEmpty();
    $found = collect($suggestions)->firstWhere('user_id', $user->id);
    expect($found)->not->toBeNull();
    expect($found['match_type'])->toBe('substring');
});

it('returns empty array for empty input', function () {
    $suggestions = $this->service->suggestUsers('');

    expect($suggestions)->toBe([]);
});

it('returns empty array for whitespace input', function () {
    $suggestions = $this->service->suggestUsers('   ');

    expect($suggestions)->toBe([]);
});

it('limits results to specified count', function () {
    for ($i = 0; $i < 5; $i++) {
        User::factory()->create(['full_name' => "Zarkov Testerson{$i}"]);
    }

    $suggestions = $this->service->suggestUsers('Zarkov', 2);

    expect(count($suggestions))->toBeLessThanOrEqual(2);
});

it('handles levenshtein matching for typos', function () {
    $user = User::factory()->create(['full_name' => 'Quirino Valbona', 'is_active' => true]);

    $suggestions = $this->service->suggestUsers('Quirimo Valbona');

    expect($suggestions)->not->toBeEmpty();
    $found = collect($suggestions)->firstWhere('user_id', $user->id);
    expect($found)->not->toBeNull();
});

it('returns overridden user_id when user_overrides provided', function () {
    $user = User::factory()->create(['full_name' => 'Override Target']);

    $result = $this->service->resolveUserId('W.Mystery', ['W.Mystery' => $user->id]);

    expect($result)->toBe($user->id);
});
