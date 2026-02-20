<?php

use App\Models\RiskCategory;
use App\Models\RiskTheme;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkItem;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

// ============================================================
// StoreWorkItemRequest
// ============================================================

describe('StoreWorkItemRequest', function () {

    it('accepts valid work item data', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/workitems', [
                'ref_no' => 'BOW-VAL-001',
                'department' => 'IT',
                'description' => 'Valid work item for validation test',
            ]);

        $response->assertCreated();
    });

    it('rejects work item without ref_no', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/workitems', [
                'department' => 'IT',
                'description' => 'Missing ref_no',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ref_no']);
    });

    it('rejects work item without description', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/workitems', [
                'ref_no' => 'BOW-VAL-002',
                'department' => 'IT',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    });

    it('rejects work item without department', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/workitems', [
                'ref_no' => 'BOW-VAL-003',
                'description' => 'Missing department',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['department']);
    });

    it('rejects duplicate ref_no', function () {
        WorkItem::create([
            'ref_no' => 'BOW-VAL-DUP',
            'department' => 'IT',
            'description' => 'First item',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/workitems', [
                'ref_no' => 'BOW-VAL-DUP',
                'department' => 'IT',
                'description' => 'Duplicate ref_no',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ref_no']);
    });
});

// ============================================================
// StoreGovernanceItemRequest
// ============================================================

describe('StoreGovernanceItemRequest', function () {

    it('accepts valid governance item data', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/governance/items', [
                'ref_no' => 'GOV-VAL-001',
                'department' => 'Compliance',
                'description' => 'Valid governance item',
            ]);

        $response->assertCreated();
    });

    it('rejects governance item without ref_no', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/governance/items', [
                'department' => 'Compliance',
                'description' => 'Missing ref_no',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ref_no']);
    });

    it('rejects governance item without description', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/governance/items', [
                'ref_no' => 'GOV-VAL-002',
                'department' => 'Compliance',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    });

    it('rejects governance item without department', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/governance/items', [
                'ref_no' => 'GOV-VAL-003',
                'description' => 'Missing department',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['department']);
    });
});

// ============================================================
// StoreRiskRequest
// ============================================================

describe('StoreRiskRequest', function () {

    beforeEach(function () {
        $this->theme = RiskTheme::create([
            'code' => 'TH-VAL',
            'name' => 'Validation Theme',
            'board_appetite' => 3,
            'order' => 0,
            'is_active' => true,
        ]);

        $this->category = RiskCategory::create([
            'theme_id' => $this->theme->id,
            'code' => 'CAT-VAL-01',
            'name' => 'Validation Category',
            'order' => 0,
            'is_active' => true,
        ]);
    });

    it('accepts valid risk data', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/risks', [
                'ref_no' => 'R-VAL-001',
                'name' => 'Valid risk',
                'category_id' => $this->category->id,
            ]);

        $response->assertCreated();
    });

    it('rejects risk without ref_no', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/risks', [
                'name' => 'Missing ref_no',
                'category_id' => $this->category->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ref_no']);
    });

    it('rejects risk without name', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/risks', [
                'ref_no' => 'R-VAL-002',
                'category_id' => $this->category->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('rejects risk without category_id', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/risks', [
                'ref_no' => 'R-VAL-003',
                'name' => 'Missing category',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    });

    it('rejects risk with non-existent category_id', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/risks', [
                'ref_no' => 'R-VAL-004',
                'name' => 'Bad category',
                'category_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    });
});

// ============================================================
// StoreSupplierRequest
// ============================================================

describe('StoreSupplierRequest', function () {

    it('accepts valid supplier with name only', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/suppliers', [
                'name' => 'Valid Supplier',
            ]);

        $response->assertCreated();
    });

    it('accepts supplier with nullable ref_no', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/suppliers', [
                'name' => 'Supplier Without Ref',
                'ref_no' => null,
            ]);

        $response->assertCreated();
    });

    it('rejects supplier without name', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/suppliers', [
                'ref_no' => 'SUP-VAL-001',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('rejects duplicate ref_no', function () {
        Supplier::create(['name' => 'First', 'ref_no' => 'SUP-DUP']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/suppliers', [
                'name' => 'Second',
                'ref_no' => 'SUP-DUP',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ref_no']);
    });
});

// ============================================================
// StoreUserRequest
// ============================================================

describe('StoreUserRequest', function () {

    it('rejects user without username', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', [
                'email' => 'test@example.com',
                'password' => 'Password1',
                'full_name' => 'Test User',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    });

    it('rejects user without email', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', [
                'username' => 'testuser',
                'password' => 'Password1',
                'full_name' => 'Test User',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects user without password', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', [
                'username' => 'testuser2',
                'email' => 'test2@example.com',
                'full_name' => 'Test User',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('rejects username with special characters', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', [
                'username' => 'bad user!',
                'email' => 'test3@example.com',
                'password' => 'Password1',
                'full_name' => 'Test User',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    });

    it('rejects weak password without mixed case', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', [
                'username' => 'testuser3',
                'email' => 'test4@example.com',
                'password' => 'password1',
                'full_name' => 'Test User',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});

// ============================================================
// LoginRequest
// ============================================================

describe('LoginRequest', function () {

    it('rejects login without email', function () {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'Password1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('rejects login without password', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});
