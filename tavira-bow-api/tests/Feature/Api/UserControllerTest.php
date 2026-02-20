<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();
});

it('requires authentication for index', function () {
    $this->getJson('/api/users')->assertUnauthorized();
});

it('returns paginated users for admin', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/users');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('forbids non-admin from listing users', function () {
    $response = $this->actingAs($this->member)->getJson('/api/users');

    $response->assertForbidden();
});

it('creates a user as admin', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/users', [
        'username' => 'newuser',
        'email' => 'newuser@test.com',
        'password' => 'Password1',
        'full_name' => 'New User',
        'role' => 'member',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.username', 'newuser');
    expect(User::where('username', 'newuser')->exists())->toBeTrue();
});

it('creates a user with department permissions', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/users', [
        'username' => 'deptuser',
        'email' => 'deptuser@test.com',
        'password' => 'Password1',
        'full_name' => 'Dept User',
        'role' => 'member',
        'primary_department' => 'Finance',
        'department_permissions' => [
            [
                'department' => 'Finance',
                'can_view' => true,
                'can_edit_status' => true,
                'can_create_tasks' => false,
                'can_edit_all' => false,
            ],
        ],
    ]);

    $response->assertCreated();
    $user = User::where('username', 'deptuser')->first();
    expect($user->departmentPermissions)->toHaveCount(1);
    expect($user->departmentPermissions->first()->department)->toBe('Finance');
});

it('forbids non-admin from creating users', function () {
    $response = $this->actingAs($this->member)->postJson('/api/users', [
        'username' => 'forbidden',
        'email' => 'forbidden@test.com',
        'password' => 'Password1',
        'full_name' => 'Forbidden User',
    ]);

    $response->assertForbidden();
});

it('returns 422 when creating user without required fields', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/users', [
        'full_name' => 'Missing fields',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username', 'email', 'password']);
});

it('returns 422 when creating user with duplicate username', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/users', [
        'username' => $this->admin->username,
        'email' => 'unique@test.com',
        'password' => 'Password1',
        'full_name' => 'Dup User',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

it('returns 422 when creating user with duplicate email', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/users', [
        'username' => 'uniqueuser',
        'email' => $this->admin->email,
        'password' => 'Password1',
        'full_name' => 'Dup Email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns 422 when creating user with weak password', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/users', [
        'username' => 'weakpass',
        'email' => 'weakpass@test.com',
        'password' => 'short',
        'full_name' => 'Weak Pass',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('shows a user for admin', function () {
    $response = $this->actingAs($this->admin)->getJson("/api/users/{$this->member->id}");

    $response->assertOk()
        ->assertJsonPath('user.username', $this->member->username);
});

it('allows user to view own profile', function () {
    $response = $this->actingAs($this->member)->getJson("/api/users/{$this->member->id}");

    $response->assertOk()
        ->assertJsonPath('user.id', $this->member->id);
});

it('forbids non-admin from viewing other users', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($this->member)->getJson("/api/users/{$other->id}");

    $response->assertForbidden();
});

it('updates a user as admin', function () {
    $response = $this->actingAs($this->admin)->putJson("/api/users/{$this->member->id}", [
        'full_name' => 'Updated Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.full_name', 'Updated Name');
});

it('allows user to update own profile', function () {
    $response = $this->actingAs($this->member)->putJson("/api/users/{$this->member->id}", [
        'full_name' => 'Self Updated',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.full_name', 'Self Updated');
});

it('deletes a user as admin', function () {
    $toDelete = User::factory()->create();

    $response = $this->actingAs($this->admin)->deleteJson("/api/users/{$toDelete->id}");

    $response->assertOk();
    expect(User::find($toDelete->id))->toBeNull();
});

it('forbids non-admin from deleting users', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($this->member)->deleteJson("/api/users/{$other->id}");

    $response->assertForbidden();
});

it('forbids admin from deleting own account', function () {
    $response = $this->actingAs($this->admin)->deleteJson("/api/users/{$this->admin->id}");

    $response->assertForbidden();
});

it('returns 404 for non-existent user', function () {
    $response = $this->actingAs($this->admin)->getJson('/api/users/99999');

    $response->assertNotFound();
});
