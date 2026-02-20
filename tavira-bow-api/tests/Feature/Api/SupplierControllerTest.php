<?php

use App\Models\Supplier;
use App\Models\SupplierAccess;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();
});

it('requires authentication for index', function () {
    $this->getJson('/api/suppliers')->assertUnauthorized();
});

it('returns paginated suppliers for admin', function () {
    Supplier::create([
        'name' => 'Paginated Test Supplier',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/suppliers');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
});

it('filters suppliers by explicit access for non-admin', function () {
    $visibleSupplier = Supplier::create([
        'name' => 'Visible Supplier ZZZ',
        'status' => 'Active',
    ]);
    SupplierAccess::create([
        'supplier_id' => $visibleSupplier->id,
        'user_id' => $this->member->id,
        'can_view' => true,
        'can_edit' => false,
    ]);

    $hiddenSupplier = Supplier::create([
        'name' => 'Hidden Supplier ZZZ',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->member)->getJson('/api/suppliers');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Visible Supplier ZZZ');
    expect($names)->not->toContain('Hidden Supplier ZZZ');
});

it('forbids non-admin from creating a supplier', function () {
    $response = $this->actingAs($this->member)->postJson('/api/suppliers', [
        'name' => 'Forbidden Supplier',
        'status' => 'Active',
    ]);

    $response->assertForbidden();
});

it('returns 422 when creating supplier without required name', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/suppliers', [
        'status' => 'Active',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('shows a single supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Show Supplier',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->admin)->getJson("/api/suppliers/{$supplier->id}");

    $response->assertOk()
        ->assertJsonPath('supplier.name', 'Show Supplier');
});

it('returns 404 for non-existent supplier', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/suppliers/99999');

    $response->assertNotFound();
});

it('updates a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Before Update',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/suppliers/{$supplier->id}", [
        'name' => 'After Update',
    ]);

    $response->assertOk()
        ->assertJsonPath('supplier.name', 'After Update');
});

it('deletes a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'To Delete',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/suppliers/{$supplier->id}");

    $response->assertOk();
    expect(Supplier::find($supplier->id))->toBeNull();
});

it('returns supplier dashboard stats', function () {
    Supplier::create([
        'name' => 'Dashboard Active',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/suppliers-dashboard');

    $response->assertOk()
        ->assertJsonStructure([
            'total_suppliers',
            'active_suppliers',
            'total_contracts',
            'expiring_soon',
            'total_invoices',
            'pending_invoices',
            'by_location',
            'by_category',
            'by_status',
            'expiring_contracts',
        ]);
    expect($response->json('total_suppliers'))->toBeGreaterThanOrEqual(1);
});

it('adds access to a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Access Supplier',
        'status' => 'Active',
    ]);
    $otherUser = User::factory()->create();

    $response = $this->actingAs($this->admin)->postJson("/api/suppliers/{$supplier->id}/access", [
        'user_id' => $otherUser->id,
        'access_level' => 'read',
    ]);

    $response->assertCreated();
    expect(SupplierAccess::where('supplier_id', $supplier->id)->where('user_id', $otherUser->id)->exists())->toBeTrue();
});

it('removes access from a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Remove Access Supplier',
        'status' => 'Active',
    ]);
    $otherUser = User::factory()->create();
    $access = SupplierAccess::create([
        'supplier_id' => $supplier->id,
        'user_id' => $otherUser->id,
        'can_view' => true,
        'can_edit' => true,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/suppliers/{$supplier->id}/access/{$access->id}");

    $response->assertNoContent();
    expect(SupplierAccess::find($access->id))->toBeNull();
});

it('forbids non-admin without access from viewing supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Forbidden Supplier',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->member)->getJson("/api/suppliers/{$supplier->id}");

    $response->assertForbidden();
});

it('requires authentication for show', function () {
    $supplier = Supplier::create([
        'name' => 'Auth Test',
        'status' => 'Active',
    ]);

    $this->getJson("/api/suppliers/{$supplier->id}")->assertUnauthorized();
});

it('filters suppliers by status', function () {
    Supplier::create([
        'name' => 'Active Filter Test',
        'status' => 'Active',
    ]);
    Supplier::create([
        'name' => 'Exited Filter Test',
        'status' => 'Exited',
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/suppliers?status=Active');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Active Filter Test');
    expect($names)->not->toContain('Exited Filter Test');
});
