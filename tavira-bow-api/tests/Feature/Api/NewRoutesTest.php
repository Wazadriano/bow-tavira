<?php

declare(strict_types=1);

use App\Models\GovernanceItem;
use App\Models\GovernanceItemAccess;
use App\Models\Risk;
use App\Models\RiskAction;
use App\Models\RiskCategory;
use App\Models\Supplier;
use App\Models\SupplierAccess;
use App\Models\TaskDependency;
use App\Models\User;
use App\Models\WorkItem;
use Database\Seeders\RiskThemeSeeder;

beforeEach(function () {
    $this->seed(RiskThemeSeeder::class);
    $this->user = User::factory()->admin()->create();
});

it('returns paginated list from GET /api/invoices', function () {
    $response = $this->actingAs($this->user)->getJson('/api/invoices');

    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'links', 'meta']);
});

it('returns paginated list from GET /api/contracts', function () {
    $response = $this->actingAs($this->user)->getJson('/api/contracts');

    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'links', 'meta']);
});

it('returns paginated list from GET /api/risks/actions/all', function () {
    $category = RiskCategory::first();
    $risk = Risk::create([
        'ref_no' => 'R-001',
        'category_id' => $category->id,
        'name' => 'Test Risk',
    ]);
    RiskAction::create([
        'risk_id' => $risk->id,
        'title' => 'Action 1',
        'due_date' => now()->addDays(7),
        'status' => 'Open',
        'priority' => 'Medium',
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/risks/actions/all');

    $response->assertStatus(200);
    $response->assertJsonStructure(['data', 'links', 'meta']);
});

it('recalculates single risk from POST /api/risks/{id}/recalculate', function () {
    $category = RiskCategory::first();
    $risk = Risk::create([
        'ref_no' => 'R-RECALC',
        'category_id' => $category->id,
        'name' => 'Recalc Risk',
        'financial_impact' => 2,
        'regulatory_impact' => 1,
        'reputational_impact' => 1,
        'inherent_probability' => 2,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/risks/{$risk->id}/recalculate");

    $response->assertStatus(200);
    $response->assertJsonStructure(['risk']);
});

it('creates dependency from POST /api/workitems/{id}/dependencies/{depId}', function () {
    $wi1 = WorkItem::create([
        'ref_no' => 'WI-A',
        'department' => 'IT',
        'description' => 'Item A',
        'current_status' => 'Not Started',
    ]);
    $wi2 = WorkItem::create([
        'ref_no' => 'WI-B',
        'department' => 'IT',
        'description' => 'Item B',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/workitems/{$wi1->id}/dependencies/{$wi2->id}");

    $response->assertStatus(201);
    $response->assertJsonStructure(['dependency']);
    expect(TaskDependency::where('work_item_id', $wi1->id)->where('depends_on_id', $wi2->id)->exists())->toBeTrue();
});

it('returns 422 when adding self-dependency', function () {
    $wi = WorkItem::create([
        'ref_no' => 'WI-SELF',
        'department' => 'IT',
        'description' => 'Item',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/workitems/{$wi->id}/dependencies/{$wi->id}");

    $response->assertStatus(422);
});

it('removes dependency from DELETE /api/workitems/{id}/dependencies/{depId}', function () {
    $wi1 = WorkItem::create([
        'ref_no' => 'WI-X',
        'department' => 'IT',
        'description' => 'Item X',
        'current_status' => 'Not Started',
    ]);
    $wi2 = WorkItem::create([
        'ref_no' => 'WI-Y',
        'department' => 'IT',
        'description' => 'Item Y',
        'current_status' => 'Not Started',
    ]);
    TaskDependency::create(['work_item_id' => $wi1->id, 'depends_on_id' => $wi2->id]);

    $response = $this->actingAs($this->user)->deleteJson("/api/workitems/{$wi1->id}/dependencies/{$wi2->id}");

    $response->assertStatus(204);
    expect(TaskDependency::where('work_item_id', $wi1->id)->where('depends_on_id', $wi2->id)->exists())->toBeFalse();
});

it('adds governance access from POST /api/governance/items/{id}/access', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-ACC',
        'department' => 'IT',
        'description' => 'Governance item',
    ]);
    $otherUser = User::factory()->create();

    $response = $this->actingAs($this->user)->postJson("/api/governance/items/{$item->id}/access", [
        'user_id' => $otherUser->id,
        'access_level' => 'read',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['access']);
    expect(GovernanceItemAccess::where('governance_item_id', $item->id)->where('user_id', $otherUser->id)->exists())->toBeTrue();
});

it('removes governance access from DELETE /api/governance/items/{id}/access/{accessId}', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-DEL',
        'department' => 'IT',
        'description' => 'Governance item',
    ]);
    $otherUser = User::factory()->create();
    $access = GovernanceItemAccess::create([
        'governance_item_id' => $item->id,
        'user_id' => $otherUser->id,
        'can_view' => true,
        'can_edit' => false,
    ]);

    $response = $this->actingAs($this->user)->deleteJson("/api/governance/items/{$item->id}/access/{$access->id}");

    $response->assertStatus(204);
    expect(GovernanceItemAccess::find($access->id))->toBeNull();
});

it('adds supplier access from POST /api/suppliers/{id}/access', function () {
    $supplier = Supplier::create([
        'name' => 'Supplier Acc',
        'location' => 'Global',
        'status' => 'Active',
    ]);
    $otherUser = User::factory()->create();

    $response = $this->actingAs($this->user)->postJson("/api/suppliers/{$supplier->id}/access", [
        'user_id' => $otherUser->id,
        'access_level' => 'write',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['access']);
    expect(SupplierAccess::where('supplier_id', $supplier->id)->where('user_id', $otherUser->id)->exists())->toBeTrue();
});

it('removes supplier access from DELETE /api/suppliers/{id}/access/{accessId}', function () {
    $supplier = Supplier::create([
        'name' => 'Supplier Del',
        'location' => 'Global',
        'status' => 'Active',
    ]);
    $otherUser = User::factory()->create();
    $access = SupplierAccess::create([
        'supplier_id' => $supplier->id,
        'user_id' => $otherUser->id,
        'can_view' => true,
        'can_edit' => true,
    ]);

    $response = $this->actingAs($this->user)->deleteJson("/api/suppliers/{$supplier->id}/access/{$access->id}");

    $response->assertStatus(204);
    expect(SupplierAccess::find($access->id))->toBeNull();
});
