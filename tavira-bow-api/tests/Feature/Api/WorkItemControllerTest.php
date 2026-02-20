<?php

use App\Models\TaskAssignment;
use App\Models\TaskDependency;
use App\Models\User;
use App\Models\UserDepartmentPermission;
use App\Models\WorkItem;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();
});

it('requires authentication for index', function () {
    $this->getJson('/api/workitems')->assertUnauthorized();
});

it('returns paginated work items for admin', function () {
    WorkItem::create([
        'ref_no' => 'BOW-T-001',
        'description' => 'Test item',
        'department' => 'Finance',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/workitems');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
});

it('non-admin only sees own tasks (responsible or assigned)', function () {
    WorkItem::create([
        'ref_no' => 'BOW-T-FIN',
        'description' => 'My task',
        'department' => 'TestDeptA',
        'current_status' => 'Not Started',
        'responsible_party_id' => $this->member->id,
    ]);
    WorkItem::create([
        'ref_no' => 'BOW-T-IT',
        'description' => 'Someone else task',
        'department' => 'TestDeptA',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->member)->getJson('/api/workitems');

    $response->assertOk();
    $refs = collect($response->json('data'))->pluck('ref_no');
    expect($refs)->toContain('BOW-T-FIN');
    expect($refs)->not->toContain('BOW-T-IT');
});

it('filters work items by status query param', function () {
    WorkItem::create([
        'ref_no' => 'BOW-T-NS',
        'description' => 'Not started',
        'department' => 'IT',
        'current_status' => 'Not Started',
        'deadline' => now()->addDays(1),
    ]);
    WorkItem::create([
        'ref_no' => 'BOW-T-IP',
        'description' => 'In progress',
        'department' => 'IT',
        'current_status' => 'In Progress',
        'deadline' => now()->addDays(2),
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/workitems?status=In Progress&per_page=100');

    $response->assertOk();
    $refs = collect($response->json('data'))->pluck('ref_no');
    expect($refs)->toContain('BOW-T-IP');
    expect($refs)->not->toContain('BOW-T-NS');
});

it('filters work items by rag_status query param', function () {
    WorkItem::create([
        'ref_no' => 'BOW-T-G',
        'description' => 'Green item',
        'department' => 'IT',
        'current_status' => 'In Progress',
        'rag_status' => 'Green',
        'deadline' => now()->addDays(1),
    ]);
    WorkItem::create([
        'ref_no' => 'BOW-T-R',
        'description' => 'Red item',
        'department' => 'IT',
        'current_status' => 'In Progress',
        'rag_status' => 'Red',
        'deadline' => now()->addDays(2),
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/workitems?rag_status=Red&per_page=100');

    $response->assertOk();
    $refs = collect($response->json('data'))->pluck('ref_no');
    expect($refs)->toContain('BOW-T-R');
    expect($refs)->not->toContain('BOW-T-G');
});

it('creates a work item with valid data', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/workitems', [
        'ref_no' => 'BOW-NEW-001',
        'description' => 'New test item',
        'department' => 'Finance',
        'current_status' => 'Not Started',
    ]);

    $response->assertCreated()
        ->assertJsonPath('work_item.ref_no', 'BOW-NEW-001');
    expect(WorkItem::where('ref_no', 'BOW-NEW-001')->exists())->toBeTrue();
});

it('returns 422 when creating work item without required fields', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/workitems', [
        'description' => 'Missing ref_no and department',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ref_no', 'department']);
});

it('returns 422 when creating work item with duplicate ref_no', function () {
    WorkItem::create([
        'ref_no' => 'BOW-DUP',
        'description' => 'Original',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->postJson('/api/workitems', [
        'ref_no' => 'BOW-DUP',
        'description' => 'Duplicate',
        'department' => 'IT',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['ref_no']);
});

it('shows a single work item', function () {
    $workItem = WorkItem::create([
        'ref_no' => 'BOW-SHOW',
        'description' => 'Show test',
        'department' => 'Finance',
        'current_status' => 'In Progress',
    ]);

    $response = $this->actingAs($this->admin)->getJson("/api/workitems/{$workItem->id}");

    $response->assertOk()
        ->assertJsonPath('work_item.ref_no', 'BOW-SHOW');
});

it('returns 404 for non-existent work item', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/workitems/99999');

    $response->assertNotFound();
});

it('updates a work item', function () {
    $workItem = WorkItem::create([
        'ref_no' => 'BOW-UPD',
        'description' => 'Before update',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/workitems/{$workItem->id}", [
        'description' => 'After update',
        'current_status' => 'In Progress',
    ]);

    $response->assertOk()
        ->assertJsonPath('work_item.description', 'After update');
});

it('deletes a work item', function () {
    $workItem = WorkItem::create([
        'ref_no' => 'BOW-DEL',
        'description' => 'To be deleted',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/workitems/{$workItem->id}");

    $response->assertOk();
    expect(WorkItem::find($workItem->id))->toBeNull();
});

it('assigns a user to a work item', function () {
    $workItem = WorkItem::create([
        'ref_no' => 'BOW-ASS',
        'description' => 'Assign test',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);
    $assignee = User::factory()->create();

    $response = $this->actingAs($this->admin)->postJson("/api/workitems/{$workItem->id}/assign/{$assignee->id}");

    $response->assertCreated();
    expect(TaskAssignment::where('work_item_id', $workItem->id)->where('user_id', $assignee->id)->exists())->toBeTrue();
});

it('returns 409 when assigning already assigned user', function () {
    $workItem = WorkItem::create([
        'ref_no' => 'BOW-DUP-ASS',
        'description' => 'Dup assign test',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);
    $assignee = User::factory()->create();
    TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'user_id' => $assignee->id,
        'assignment_type' => 'member',
    ]);

    $response = $this->actingAs($this->admin)->postJson("/api/workitems/{$workItem->id}/assign/{$assignee->id}");

    $response->assertStatus(409);
});

it('unassigns a user from a work item', function () {
    $workItem = WorkItem::create([
        'ref_no' => 'BOW-UNASS',
        'description' => 'Unassign test',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);
    $assignee = User::factory()->create();
    TaskAssignment::create([
        'work_item_id' => $workItem->id,
        'user_id' => $assignee->id,
        'assignment_type' => 'member',
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/workitems/{$workItem->id}/assign/{$assignee->id}");

    $response->assertNoContent();
    expect(TaskAssignment::where('work_item_id', $workItem->id)->where('user_id', $assignee->id)->exists())->toBeFalse();
});

it('adds a dependency between work items', function () {
    $wi1 = WorkItem::create([
        'ref_no' => 'BOW-DEP-A',
        'description' => 'Item A',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);
    $wi2 = WorkItem::create([
        'ref_no' => 'BOW-DEP-B',
        'description' => 'Item B',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->postJson("/api/workitems/{$wi1->id}/dependencies/{$wi2->id}");

    $response->assertCreated();
    expect(TaskDependency::where('work_item_id', $wi1->id)->where('depends_on_id', $wi2->id)->exists())->toBeTrue();
});

it('returns 422 for self-dependency', function () {
    $wi = WorkItem::create([
        'ref_no' => 'BOW-SELF',
        'description' => 'Self ref',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->postJson("/api/workitems/{$wi->id}/dependencies/{$wi->id}");

    $response->assertUnprocessable();
});

it('removes a dependency', function () {
    $wi1 = WorkItem::create([
        'ref_no' => 'BOW-RDEP-A',
        'description' => 'Item A',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);
    $wi2 = WorkItem::create([
        'ref_no' => 'BOW-RDEP-B',
        'description' => 'Item B',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);
    TaskDependency::create(['work_item_id' => $wi1->id, 'depends_on_id' => $wi2->id]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/workitems/{$wi1->id}/dependencies/{$wi2->id}");

    $response->assertNoContent();
    expect(TaskDependency::where('work_item_id', $wi1->id)->where('depends_on_id', $wi2->id)->exists())->toBeFalse();
});

it('forbids non-admin without department permission from viewing item', function () {
    $workItem = WorkItem::create([
        'ref_no' => 'BOW-FORBID',
        'description' => 'Forbidden item',
        'department' => 'ForbiddenDept',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->member)->getJson("/api/workitems/{$workItem->id}");

    $response->assertForbidden();
});

it('searches work items by description', function () {
    WorkItem::create([
        'ref_no' => 'BOW-SEARCH-1',
        'description' => 'Unique searchable description xyz123',
        'department' => 'IT',
        'current_status' => 'Not Started',
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/workitems?search=xyz123');

    $response->assertOk();
    $refs = collect($response->json('data'))->pluck('ref_no');
    expect($refs)->toContain('BOW-SEARCH-1');
});
