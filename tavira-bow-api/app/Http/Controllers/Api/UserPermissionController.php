<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDepartmentPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function index(User $user): JsonResponse
    {
        $permissions = $user->departmentPermissions()->with('user')->get();

        return response()->json($permissions);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'department' => 'required|string',
            'can_view' => 'boolean',
            'can_edit_status' => 'boolean',
            'can_create_tasks' => 'boolean',
            'can_edit_all' => 'boolean',
        ]);

        $permission = $user->departmentPermissions()->create($validated);

        return response()->json($permission, 201);
    }

    public function update(Request $request, User $user, UserDepartmentPermission $permission): JsonResponse
    {
        $validated = $request->validate([
            'can_view' => 'boolean',
            'can_edit_status' => 'boolean',
            'can_create_tasks' => 'boolean',
            'can_edit_all' => 'boolean',
        ]);

        $permission->update($validated);

        return response()->json($permission);
    }

    public function destroy(User $user, UserDepartmentPermission $permission): JsonResponse
    {
        $permission->delete();

        return response()->json(null, 204);
    }
}
