<?php

use App\Models\Risk;
use App\Models\RiskCategory;
use App\Models\RiskTheme;
use App\Models\RiskThemePermission;
use App\Models\Supplier;
use App\Models\SupplierAccess;
use App\Models\User;
use App\Models\UserDepartmentPermission;
use App\Models\WorkItem;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();

    $this->theme = RiskTheme::create([
        'code' => 'TH-TEST',
        'name' => 'Test Theme',
        'board_appetite' => 3,
        'order' => 0,
        'is_active' => true,
    ]);

    $this->category = RiskCategory::create([
        'theme_id' => $this->theme->id,
        'code' => 'CAT-TEST-01',
        'name' => 'Test Category',
        'order' => 0,
        'is_active' => true,
    ]);
});

// ============================================================
// WorkItemPolicy - Department Layer
// ============================================================

describe('WorkItemPolicy - department layer', function () {

    it('grants admin view on any work item', function () {
        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-001',
            'department' => 'Finance',
            'description' => 'Admin view test',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/workitems/{$workItem->id}");

        $response->assertOk();
    });

    it('grants member with can_view on matching department', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'Finance',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => false,
            'can_edit_all' => false,
        ]);

        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-002',
            'department' => 'Finance',
            'description' => 'Member view test',
        ]);

        $response = $this->actingAs($this->member)
            ->getJson("/api/workitems/{$workItem->id}");

        $response->assertOk();
    });

    it('denies member without permission on department', function () {
        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-003',
            'department' => 'Finance',
            'description' => 'Forbidden view test',
        ]);

        $response = $this->actingAs($this->member)
            ->getJson("/api/workitems/{$workItem->id}");

        $response->assertForbidden();
    });

    it('denies member with permission on different department', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'IT',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => false,
            'can_edit_all' => false,
        ]);

        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-004',
            'department' => 'Finance',
            'description' => 'Wrong department test',
        ]);

        $response = $this->actingAs($this->member)
            ->getJson("/api/workitems/{$workItem->id}");

        $response->assertForbidden();
    });

    it('grants admin create on work items', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/workitems', [
                'ref_no' => 'BOW-ADM-010',
                'department' => 'Finance',
                'description' => 'Admin create test',
            ]);

        $response->assertCreated();
    });

    it('grants member with can_create_tasks on matching department', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'Finance',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => true,
            'can_edit_all' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->postJson('/api/workitems', [
                'ref_no' => 'BOW-MEM-011',
                'department' => 'Finance',
                'description' => 'Member create test',
            ]);

        $response->assertCreated();
    });

    it('denies member without can_create_tasks', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'Finance',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => false,
            'can_edit_all' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->postJson('/api/workitems', [
                'ref_no' => 'BOW-MEM-012',
                'department' => 'Finance',
                'description' => 'Denied create test',
            ]);

        $response->assertForbidden();
    });

    it('grants admin update on any work item', function () {
        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-020',
            'department' => 'Finance',
            'description' => 'Admin update test',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/workitems/{$workItem->id}", [
                'description' => 'Updated by admin',
            ]);

        $response->assertOk();
    });

    it('grants member with can_edit_all on matching department', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'Finance',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => false,
            'can_edit_all' => true,
        ]);

        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-021',
            'department' => 'Finance',
            'description' => 'Member edit test',
        ]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/workitems/{$workItem->id}", [
                'description' => 'Updated by member with edit_all',
            ]);

        $response->assertOk();
    });

    it('grants responsible party update even without department permission', function () {
        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-022',
            'department' => 'Finance',
            'description' => 'Responsible party test',
            'responsible_party_id' => $this->member->id,
        ]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/workitems/{$workItem->id}", [
                'description' => 'Updated by responsible party',
            ]);

        $response->assertOk();
    });

    it('denies member with only can_view from updating', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'Finance',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => false,
            'can_edit_all' => false,
        ]);

        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-023',
            'department' => 'Finance',
            'description' => 'View-only member test',
        ]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/workitems/{$workItem->id}", [
                'description' => 'Should be forbidden',
            ]);

        $response->assertForbidden();
    });

    it('grants admin delete on any work item', function () {
        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-030',
            'department' => 'Finance',
            'description' => 'Admin delete test',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/workitems/{$workItem->id}");

        $response->assertOk();
    });

    it('denies member without can_edit_all from deleting', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'Finance',
            'can_view' => true,
            'can_edit_status' => true,
            'can_create_tasks' => true,
            'can_edit_all' => false,
        ]);

        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-031',
            'department' => 'Finance',
            'description' => 'Member delete denied',
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/workitems/{$workItem->id}");

        $response->assertForbidden();
    });

    it('grants member with can_edit_all delete on matching department', function () {
        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'Finance',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => false,
            'can_edit_all' => true,
        ]);

        $workItem = WorkItem::create([
            'ref_no' => 'BOW-PM-032',
            'department' => 'Finance',
            'description' => 'Member delete test',
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/workitems/{$workItem->id}");

        $response->assertOk();
    });
});

// ============================================================
// RiskPolicy - Theme Layer
// ============================================================

describe('RiskPolicy - theme layer', function () {

    it('grants admin view on any risk', function () {
        $risk = Risk::create([
            'ref_no' => 'R-TEST-001',
            'name' => 'Admin view risk',
            'category_id' => $this->category->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/risks/{$risk->id}");

        $response->assertOk();
    });

    it('grants member with can_view on theme', function () {
        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => false,
            'can_create' => false,
            'can_delete' => false,
        ]);

        $risk = Risk::create([
            'ref_no' => 'R-TEST-002',
            'name' => 'Member view risk',
            'category_id' => $this->category->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson("/api/risks/{$risk->id}");

        $response->assertOk();
    });

    it('denies member without theme permission', function () {
        $risk = Risk::create([
            'ref_no' => 'R-TEST-003',
            'name' => 'Denied view risk',
            'category_id' => $this->category->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson("/api/risks/{$risk->id}");

        $response->assertForbidden();
    });

    it('grants member with can_create on theme', function () {
        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => false,
            'can_create' => true,
            'can_delete' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->postJson('/api/risks', [
                'ref_no' => 'R-TEST-CREATE-01',
                'name' => 'Member created risk',
                'category_id' => $this->category->id,
                'financial_impact' => 2,
                'regulatory_impact' => 1,
                'reputational_impact' => 1,
                'inherent_probability' => 2,
            ]);

        $response->assertCreated();
    });

    it('denies member without can_create on theme', function () {
        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => false,
            'can_create' => false,
            'can_delete' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->postJson('/api/risks', [
                'ref_no' => 'R-TEST-CREATE-02',
                'name' => 'Denied create risk',
                'category_id' => $this->category->id,
                'financial_impact' => 2,
                'regulatory_impact' => 1,
                'reputational_impact' => 1,
                'inherent_probability' => 2,
            ]);

        $response->assertForbidden();
    });

    it('grants member with can_edit on theme', function () {
        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => true,
            'can_create' => false,
            'can_delete' => false,
        ]);

        $risk = Risk::create([
            'ref_no' => 'R-TEST-EDIT-01',
            'name' => 'Editable risk',
            'category_id' => $this->category->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/risks/{$risk->id}", [
                'name' => 'Updated by member with theme edit',
            ]);

        $response->assertOk();
    });

    it('grants risk owner update even without theme edit permission', function () {
        $risk = Risk::create([
            'ref_no' => 'R-TEST-OWN-01',
            'name' => 'Owner risk',
            'category_id' => $this->category->id,
            'owner_id' => $this->member->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/risks/{$risk->id}", [
                'name' => 'Updated by owner',
            ]);

        $response->assertOk();
    });

    it('grants risk responsible party update even without theme edit permission', function () {
        $risk = Risk::create([
            'ref_no' => 'R-TEST-RP-01',
            'name' => 'RP risk',
            'category_id' => $this->category->id,
            'responsible_party_id' => $this->member->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/risks/{$risk->id}", [
                'name' => 'Updated by responsible party',
            ]);

        $response->assertOk();
    });

    it('grants member with can_delete on theme', function () {
        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => false,
            'can_create' => false,
            'can_delete' => true,
        ]);

        $risk = Risk::create([
            'ref_no' => 'R-TEST-DEL-01',
            'name' => 'Deletable risk',
            'category_id' => $this->category->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/risks/{$risk->id}");

        $response->assertOk();
    });

    it('denies member without can_delete on theme', function () {
        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => true,
            'can_create' => true,
            'can_delete' => false,
        ]);

        $risk = Risk::create([
            'ref_no' => 'R-TEST-DEL-02',
            'name' => 'Not deletable risk',
            'category_id' => $this->category->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/risks/{$risk->id}");

        $response->assertForbidden();
    });

    it('denies member update when they have only view permission on theme', function () {
        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => false,
            'can_create' => false,
            'can_delete' => false,
        ]);

        $risk = Risk::create([
            'ref_no' => 'R-TEST-NOEDIT-01',
            'name' => 'View-only risk',
            'category_id' => $this->category->id,
            'financial_impact' => 2,
            'regulatory_impact' => 1,
            'reputational_impact' => 1,
            'inherent_probability' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->putJson("/api/risks/{$risk->id}", [
                'name' => 'Should be forbidden',
            ]);

        $response->assertForbidden();
    });
});

// ============================================================
// SupplierPolicy - Access Layer (tested via Gate directly)
// The suppliers table lacks ref_no and responsible_party_id
// columns, so we test the policy layer directly.
// Note: supplier_access table only has can_view and can_edit
// columns. The policy's delete() checks can_delete which does
// not exist yet, so member delete is always denied.
// ============================================================

describe('SupplierPolicy - access layer', function () {

    it('grants admin view on any supplier', function () {
        $supplier = Supplier::create(['name' => 'Test Supplier View']);

        expect($this->admin->can('view', $supplier))->toBeTrue();
    });

    it('grants admin create supplier', function () {
        expect($this->admin->can('create', Supplier::class))->toBeTrue();
    });

    it('grants admin update on any supplier', function () {
        $supplier = Supplier::create(['name' => 'Test Supplier Update']);

        expect($this->admin->can('update', $supplier))->toBeTrue();
    });

    it('grants admin delete on any supplier', function () {
        $supplier = Supplier::create(['name' => 'Test Supplier Delete']);

        expect($this->admin->can('delete', $supplier))->toBeTrue();
    });

    it('denies member create supplier', function () {
        expect($this->member->can('create', Supplier::class))->toBeFalse();
    });

    it('grants member with explicit can_view access', function () {
        $supplier = Supplier::create(['name' => 'Accessible Supplier']);

        SupplierAccess::create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->member->id,
            'can_view' => true,
            'can_edit' => false,
        ]);

        expect($this->member->can('view', $supplier))->toBeTrue();
    });

    it('denies member without any access', function () {
        $supplier = Supplier::create(['name' => 'Restricted Supplier']);

        expect($this->member->can('view', $supplier))->toBeFalse();
    });

    it('grants member with explicit can_edit access', function () {
        $supplier = Supplier::create(['name' => 'Editable Supplier']);

        SupplierAccess::create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->member->id,
            'can_view' => true,
            'can_edit' => true,
        ]);

        expect($this->member->can('update', $supplier))->toBeTrue();
    });

    it('denies member with only can_view from updating', function () {
        $supplier = Supplier::create(['name' => 'View-Only Supplier']);

        SupplierAccess::create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->member->id,
            'can_view' => true,
            'can_edit' => false,
        ]);

        expect($this->member->can('update', $supplier))->toBeFalse();
    });

    it('grants member with can_delete on supplier', function () {
        $supplier = Supplier::create(['name' => 'Deletable Supplier']);

        SupplierAccess::create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->member->id,
            'can_view' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);

        expect($this->member->can('delete', $supplier))->toBeTrue();
    });

    it('denies member without can_delete on supplier', function () {
        $supplier = Supplier::create(['name' => 'Not Deletable Supplier']);

        SupplierAccess::create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->member->id,
            'can_view' => true,
            'can_edit' => true,
            'can_delete' => false,
        ]);

        expect($this->member->can('delete', $supplier))->toBeFalse();
    });

    it('denies member with can_view false', function () {
        $supplier = Supplier::create(['name' => 'No View Supplier']);

        SupplierAccess::create([
            'supplier_id' => $supplier->id,
            'user_id' => $this->member->id,
            'can_view' => false,
            'can_edit' => false,
        ]);

        expect($this->member->can('view', $supplier))->toBeFalse();
    });
});

// ============================================================
// Dashboard Scope Filtering - Risk
// ============================================================

describe('Risk dashboard scope filtering', function () {

    it('admin sees all risks in dashboard', function () {
        Risk::query()->delete();

        Risk::create([
            'ref_no' => 'R-DASH-01',
            'name' => 'Risk in test theme',
            'category_id' => $this->category->id,
            'financial_impact' => 5,
            'regulatory_impact' => 5,
            'reputational_impact' => 5,
            'inherent_probability' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/risks/dashboard');

        $response->assertOk();
        expect($response->json('total_risks'))->toBe(1);
    });

    it('member only sees risks from permitted themes in dashboard', function () {
        Risk::query()->delete();

        $otherTheme = RiskTheme::create([
            'code' => 'TH-OTHER',
            'name' => 'Other Theme',
            'board_appetite' => 3,
            'order' => 1,
            'is_active' => true,
        ]);

        $otherCategory = RiskCategory::create([
            'theme_id' => $otherTheme->id,
            'code' => 'CAT-OTHER',
            'name' => 'Other Category',
            'order' => 0,
            'is_active' => true,
        ]);

        Risk::create([
            'ref_no' => 'R-DASH-02',
            'name' => 'Visible risk',
            'category_id' => $this->category->id,
            'financial_impact' => 5,
            'regulatory_impact' => 5,
            'reputational_impact' => 5,
            'inherent_probability' => 5,
            'is_active' => true,
            'owner_id' => $this->member->id,
        ]);

        Risk::create([
            'ref_no' => 'R-DASH-03',
            'name' => 'Hidden risk',
            'category_id' => $otherCategory->id,
            'financial_impact' => 5,
            'regulatory_impact' => 5,
            'reputational_impact' => 5,
            'inherent_probability' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/risks/dashboard');

        $response->assertOk();
        expect($response->json('total_risks'))->toBe(1);
    });

    it('member without any theme permission sees zero risks in dashboard', function () {
        Risk::query()->delete();

        Risk::create([
            'ref_no' => 'R-DASH-04',
            'name' => 'Invisible risk',
            'category_id' => $this->category->id,
            'financial_impact' => 5,
            'regulatory_impact' => 5,
            'reputational_impact' => 5,
            'inherent_probability' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/risks/dashboard');

        $response->assertOk();
        expect($response->json('total_risks'))->toBe(0);
    });
});

// ============================================================
// Dashboard Scope Filtering - Supplier
// ============================================================

describe('Supplier dashboard scope filtering', function () {

    it('admin sees all suppliers in dashboard', function () {
        Supplier::query()->delete();

        Supplier::create(['name' => 'Supplier A', 'status' => 'Active']);
        Supplier::create(['name' => 'Supplier B', 'status' => 'Active']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/suppliers-dashboard');

        $response->assertOk();
        expect($response->json('total_suppliers'))->toBe(2);
    });

    it('member only sees accessible suppliers in dashboard', function () {
        Supplier::query()->delete();

        $visible = Supplier::create(['name' => 'Visible Supplier', 'status' => 'Active']);
        Supplier::create(['name' => 'Hidden Supplier', 'status' => 'Active']);

        SupplierAccess::create([
            'supplier_id' => $visible->id,
            'user_id' => $this->member->id,
            'can_view' => true,
            'can_edit' => false,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/suppliers-dashboard');

        $response->assertOk();
        expect($response->json('total_suppliers'))->toBe(1);
    });

    it('member without access sees zero suppliers in dashboard', function () {
        Supplier::query()->delete();

        Supplier::create(['name' => 'No Access Supplier', 'status' => 'Active']);

        $response = $this->actingAs($this->member)
            ->getJson('/api/suppliers-dashboard');

        $response->assertOk();
        expect($response->json('total_suppliers'))->toBe(0);
    });
});

// ============================================================
// Dashboard Scope Filtering - Global Dashboard
// ============================================================

describe('Global dashboard scope filtering', function () {

    it('member only sees work items from permitted departments in stats', function () {
        WorkItem::query()->delete();

        UserDepartmentPermission::create([
            'user_id' => $this->member->id,
            'department' => 'IT',
            'can_view' => true,
            'can_edit_status' => false,
            'can_create_tasks' => false,
            'can_edit_all' => false,
        ]);

        WorkItem::create([
            'ref_no' => 'WI-DASH-01',
            'department' => 'IT',
            'description' => 'Visible task',
            'current_status' => 'In Progress',
            'responsible_party_id' => $this->member->id,
        ]);

        WorkItem::create([
            'ref_no' => 'WI-DASH-02',
            'department' => 'Finance',
            'description' => 'Hidden task',
            'current_status' => 'In Progress',
        ]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/dashboard/stats');

        $response->assertOk();
        expect($response->json('total_tasks'))->toBe(1);
    });

    it('member only sees permitted risks in global dashboard alerts', function () {
        WorkItem::query()->delete();
        Risk::query()->delete();

        Risk::create([
            'ref_no' => 'R-ALERT-01',
            'name' => 'High risk visible',
            'category_id' => $this->category->id,
            'financial_impact' => 5,
            'regulatory_impact' => 5,
            'reputational_impact' => 5,
            'inherent_probability' => 5,
            'inherent_rag' => 'Red',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/dashboard/alerts');

        $response->assertOk();

        $highRiskAlerts = collect($response->json('data'))
            ->where('type', 'high_risk');
        expect($highRiskAlerts)->toBeEmpty();
    });

    it('member with theme permission sees risk alerts', function () {
        WorkItem::query()->delete();
        Risk::query()->delete();

        RiskThemePermission::create([
            'user_id' => $this->member->id,
            'theme_id' => $this->theme->id,
            'can_view' => true,
            'can_edit' => false,
            'can_create' => false,
            'can_delete' => false,
        ]);

        Risk::create([
            'ref_no' => 'R-ALERT-02',
            'name' => 'High risk for member',
            'category_id' => $this->category->id,
            'financial_impact' => 5,
            'regulatory_impact' => 5,
            'reputational_impact' => 5,
            'inherent_probability' => 5,
            'inherent_rag' => 'Red',
            'is_active' => true,
            'owner_id' => $this->member->id,
        ]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/dashboard/alerts');

        $response->assertOk();

        $highRiskAlerts = collect($response->json('data'))
            ->where('type', 'high_risk');
        expect($highRiskAlerts)->toHaveCount(1);
    });
});

// ============================================================
// Cross-cutting: unauthenticated access
// ============================================================

describe('unauthenticated access', function () {

    it('rejects unauthenticated work item access', function () {
        $response = $this->getJson('/api/workitems');

        $response->assertUnauthorized();
    });

    it('rejects unauthenticated risk access', function () {
        $response = $this->getJson('/api/risks');

        $response->assertUnauthorized();
    });

    it('rejects unauthenticated supplier access', function () {
        $response = $this->getJson('/api/suppliers');

        $response->assertUnauthorized();
    });
});
