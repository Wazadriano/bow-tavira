<?php

use App\Models\GovernanceItem;
use App\Models\GovernanceItemAccess;
use App\Models\User;
use App\Models\UserDepartmentPermission;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();
});

it('requires authentication for index', function () {
    $this->getJson('/api/governance/items')->assertUnauthorized();
});

it('returns paginated governance items for admin', function () {
    GovernanceItem::create([
        'ref_no' => 'GOV-T-001',
        'activity' => 'Board Meeting',
        'description' => 'Quarterly board meeting',
        'department' => 'Corporate Governance',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/governance/items');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
});

it('non-admin only sees own governance items (responsible or explicit access)', function () {
    GovernanceItem::create([
        'ref_no' => 'GOV-T-FIN',
        'description' => 'My governance item',
        'department' => 'TestGovDeptA',
        'responsible_party_id' => $this->member->id,
    ]);
    GovernanceItem::create([
        'ref_no' => 'GOV-T-IT',
        'description' => 'Someone else governance',
        'department' => 'TestGovDeptA',
    ]);

    $response = $this->actingAs($this->member)->getJson('/api/governance/items');

    $response->assertOk();
    $refs = collect($response->json('data'))->pluck('ref_no');
    expect($refs)->toContain('GOV-T-FIN');
    expect($refs)->not->toContain('GOV-T-IT');
});

it('creates a governance item with valid data', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/governance/items', [
        'ref_no' => 'GOV-NEW-001',
        'activity' => 'Compliance Review',
        'description' => 'Annual compliance review',
        'department' => 'Corporate Governance',
        'current_status' => 'Not Started',
    ]);

    $response->assertCreated()
        ->assertJsonPath('governance_item.ref_no', 'GOV-NEW-001');
    expect(GovernanceItem::where('ref_no', 'GOV-NEW-001')->exists())->toBeTrue();
});

it('returns 422 when creating governance item without required fields', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/governance/items', [
        'activity' => 'Missing required fields',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ref_no', 'description', 'department']);
});

it('returns 422 when creating governance item with duplicate ref_no', function () {
    GovernanceItem::create([
        'ref_no' => 'GOV-DUP',
        'description' => 'Original',
        'department' => 'IT',
    ]);

    $response = $this->actingAs($this->admin)->postJson('/api/governance/items', [
        'ref_no' => 'GOV-DUP',
        'description' => 'Duplicate',
        'department' => 'IT',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ref_no']);
});

it('shows a single governance item', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-SHOW',
        'description' => 'Show test',
        'department' => 'Finance',
        'current_status' => 'In Progress',
    ]);

    $response = $this->actingAs($this->admin)->getJson("/api/governance/items/{$item->id}");

    $response->assertOk()
        ->assertJsonPath('governance_item.ref_no', 'GOV-SHOW');
});

it('returns 404 for non-existent governance item', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/governance/items/99999');

    $response->assertNotFound();
});

it('updates a governance item', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-UPD',
        'description' => 'Before update',
        'department' => 'IT',
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/governance/items/{$item->id}", [
        'description' => 'After update',
        'current_status' => 'In Progress',
    ]);

    $response->assertOk()
        ->assertJsonPath('governance_item.description', 'After update');
});

it('deletes a governance item', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-DEL',
        'description' => 'To be deleted',
        'department' => 'IT',
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/governance/items/{$item->id}");

    $response->assertOk();
    expect(GovernanceItem::find($item->id))->toBeNull();
});

it('returns dashboard stats', function () {
    GovernanceItem::create([
        'ref_no' => 'GOV-DASH-1',
        'description' => 'Active',
        'department' => 'Finance',
        'current_status' => 'In Progress',
        'deadline' => now()->addDays(10),
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/governance/dashboard/stats');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total_items',
                'completed',
                'pending',
                'overdue',
                'by_department',
                'by_frequency',
                'by_status',
                'upcoming',
            ],
        ]);
    expect($response->json('data.total_items'))->toBeGreaterThanOrEqual(1);
});

it('adds access to a governance item', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-ACC',
        'description' => 'Access test',
        'department' => 'IT',
    ]);
    $otherUser = User::factory()->create();

    $response = $this->actingAs($this->admin)->postJson("/api/governance/items/{$item->id}/access", [
        'user_id' => $otherUser->id,
        'access_level' => 'read',
    ]);

    $response->assertCreated();
    expect(GovernanceItemAccess::where('governance_item_id', $item->id)->where('user_id', $otherUser->id)->exists())->toBeTrue();
});

it('removes access from a governance item', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-RACC',
        'description' => 'Remove access test',
        'department' => 'IT',
    ]);
    $otherUser = User::factory()->create();
    $access = GovernanceItemAccess::create([
        'governance_item_id' => $item->id,
        'user_id' => $otherUser->id,
        'can_view' => true,
        'can_edit' => false,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/governance/items/{$item->id}/access/{$access->id}");

    $response->assertNoContent();
    expect(GovernanceItemAccess::find($access->id))->toBeNull();
});

it('forbids non-admin without permissions from viewing item', function () {
    $item = GovernanceItem::create([
        'ref_no' => 'GOV-FORBID',
        'description' => 'Forbidden governance',
        'department' => 'ForbiddenGovDept',
    ]);

    $response = $this->actingAs($this->member)->getJson("/api/governance/items/{$item->id}");

    $response->assertForbidden();
});
