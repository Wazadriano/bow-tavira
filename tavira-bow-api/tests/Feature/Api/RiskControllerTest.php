<?php

use App\Models\Risk;
use App\Models\RiskCategory;
use App\Models\RiskTheme;
use App\Models\User;
use Database\Seeders\RiskThemeSeeder;

beforeEach(function () {
    $this->seed(RiskThemeSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();

    $this->theme = RiskTheme::first();
    $this->category = RiskCategory::firstOrCreate(
        ['code' => 'P-TEST-01'],
        [
            'theme_id' => $this->theme->id,
            'name' => 'Test Category',
            'order' => 0,
            'is_active' => true,
        ]
    );
});

it('requires authentication for index', function () {
    $this->getJson('/api/risks')->assertUnauthorized();
});

it('returns paginated risks for admin', function () {
    Risk::create([
        'ref_no' => 'R-T-001',
        'name' => 'Test Risk',
        'category_id' => $this->category->id,
        'financial_impact' => 3,
        'regulatory_impact' => 2,
        'reputational_impact' => 1,
        'inherent_probability' => 2,
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/risks');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
});

it('non-admin only sees own risks (owner or responsible)', function () {
    Risk::create([
        'ref_no' => 'R-T-VISIBLE',
        'name' => 'My Risk',
        'category_id' => $this->category->id,
        'owner_id' => $this->member->id,
    ]);

    Risk::create([
        'ref_no' => 'R-T-HIDDEN',
        'name' => 'Someone else risk',
        'category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->member)->getJson('/api/risks');

    $response->assertOk();
    $refs = collect($response->json('data'))->pluck('ref_no');
    expect($refs)->toContain('R-T-VISIBLE');
    expect($refs)->not->toContain('R-T-HIDDEN');
});

it('creates a risk with valid data', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/risks', [
        'ref_no' => 'R-NEW-001',
        'name' => 'New Risk',
        'category_id' => $this->category->id,
        'financial_impact' => 3,
        'regulatory_impact' => 2,
        'reputational_impact' => 1,
        'inherent_probability' => 2,
    ]);

    $response->assertCreated()
        ->assertJsonPath('risk.ref_no', 'R-NEW-001');
    expect(Risk::where('ref_no', 'R-NEW-001')->exists())->toBeTrue();
});

it('calculates scores on creation', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/risks', [
        'ref_no' => 'R-CALC',
        'name' => 'Calculated Risk',
        'category_id' => $this->category->id,
        'financial_impact' => 4,
        'regulatory_impact' => 2,
        'reputational_impact' => 1,
        'inherent_probability' => 3,
    ]);

    $response->assertCreated();
    $risk = Risk::where('ref_no', 'R-CALC')->first();
    expect((float) $risk->inherent_risk_score)->toBe(12.0);
});

it('returns 422 when creating risk without required fields', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/risks', [
        'name' => 'Missing category',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ref_no', 'category_id']);
});

it('returns 422 when creating risk with duplicate ref_no', function () {
    Risk::create([
        'ref_no' => 'R-DUP',
        'name' => 'Original',
        'category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->admin)->postJson('/api/risks', [
        'ref_no' => 'R-DUP',
        'name' => 'Duplicate',
        'category_id' => $this->category->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ref_no']);
});

it('shows a single risk', function () {
    $risk = Risk::create([
        'ref_no' => 'R-SHOW',
        'name' => 'Show test',
        'category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->admin)->getJson("/api/risks/{$risk->id}");

    $response->assertOk()
        ->assertJsonPath('risk.ref_no', 'R-SHOW');
});

it('returns 404 for non-existent risk', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/risks/99999');

    $response->assertNotFound();
});

it('updates a risk', function () {
    $risk = Risk::create([
        'ref_no' => 'R-UPD',
        'name' => 'Before update',
        'category_id' => $this->category->id,
        'financial_impact' => 1,
        'inherent_probability' => 1,
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/risks/{$risk->id}", [
        'name' => 'After update',
        'financial_impact' => 4,
        'inherent_probability' => 3,
    ]);

    $response->assertOk()
        ->assertJsonPath('risk.name', 'After update');
    $risk->refresh();
    expect((float) $risk->inherent_risk_score)->toBe(12.0);
});

it('deletes a risk', function () {
    $risk = Risk::create([
        'ref_no' => 'R-DEL',
        'name' => 'To be deleted',
        'category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/risks/{$risk->id}");

    $response->assertOk();
    expect(Risk::find($risk->id))->toBeNull();
});

it('recalculates a single risk', function () {
    $risk = Risk::create([
        'ref_no' => 'R-RECALC',
        'name' => 'Recalc Risk',
        'category_id' => $this->category->id,
        'financial_impact' => 3,
        'regulatory_impact' => 1,
        'reputational_impact' => 1,
        'inherent_probability' => 4,
    ]);

    $response = $this->actingAs($this->admin)->postJson("/api/risks/{$risk->id}/recalculate");

    $response->assertOk()
        ->assertJsonStructure(['risk']);
    $risk->refresh();
    expect((float) $risk->inherent_risk_score)->toBe(12.0);
});

it('returns risk dashboard stats', function () {
    Risk::create([
        'ref_no' => 'R-DASH-1',
        'name' => 'Dashboard Risk 1',
        'category_id' => $this->category->id,
        'financial_impact' => 5,
        'inherent_probability' => 5,
        'is_active' => true,
        'inherent_risk_score' => 25,
        'inherent_rag' => 'Red',
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/risks/dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'total_risks',
            'high_risks',
            'medium_risks',
            'low_risks',
            'by_theme',
            'by_tier',
            'by_rag',
        ]);
    expect($response->json('total_risks'))->toBeGreaterThanOrEqual(1);
});

it('returns heatmap data', function () {
    Risk::create([
        'ref_no' => 'R-HEAT',
        'name' => 'Heatmap Risk',
        'category_id' => $this->category->id,
        'financial_impact' => 3,
        'regulatory_impact' => 2,
        'reputational_impact' => 1,
        'inherent_probability' => 4,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/risks/heatmap');

    $response->assertOk()
        ->assertJsonStructure(['heatmap', 'total_risks']);
    expect($response->json('total_risks'))->toBeGreaterThanOrEqual(1);
});

it('forbids non-admin without theme permission from viewing risk', function () {
    $risk = Risk::create([
        'ref_no' => 'R-FORBID',
        'name' => 'Forbidden Risk',
        'category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->member)->getJson("/api/risks/{$risk->id}");

    $response->assertForbidden();
});

it('recalculates all risks globally', function () {
    Risk::create([
        'ref_no' => 'R-GLOBAL-1',
        'name' => 'Global Recalc',
        'category_id' => $this->category->id,
        'financial_impact' => 2,
        'inherent_probability' => 3,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->postJson('/api/risks/recalculate');

    $response->assertOk()
        ->assertJsonStructure(['message', 'count']);
    expect($response->json('count'))->toBeGreaterThanOrEqual(1);
});
