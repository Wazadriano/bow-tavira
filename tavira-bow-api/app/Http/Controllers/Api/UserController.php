<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserDepartmentPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * List all users
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->with(['departmentPermissions']);

        // Filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('department')) {
            $query->where('primary_department', $request->department);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('full_name', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'full_name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 25), 100);

        return UserResource::collection($query->paginate($perPage));
    }

    /**
     * Create new user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'role' => $request->role ?? 'member',
                'is_active' => $request->is_active ?? true,
                'primary_department' => $request->primary_department,
            ]);

            // Create department permissions
            if ($request->has('department_permissions')) {
                foreach ($request->department_permissions as $permission) {
                    UserDepartmentPermission::create([
                        'user_id' => $user->id,
                        'department' => $permission['department'],
                        'can_view' => $permission['can_view'] ?? false,
                        'can_edit_status' => $permission['can_edit_status'] ?? false,
                        'can_create_tasks' => $permission['can_create_tasks'] ?? false,
                        'can_edit_all' => $permission['can_edit_all'] ?? false,
                    ]);
                }
            }

            return $user;
        });

        $user->load('departmentPermissions');

        return response()->json([
            'message' => 'User created successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Get single user
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['departmentPermissions', 'riskThemePermissions']);

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::transaction(function () use ($request, $user) {
            $data = $request->only([
                'username',
                'email',
                'full_name',
                'role',
                'is_active',
                'primary_department',
            ]);

            if ($request->has('password') && $request->password) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            // Update department permissions
            if ($request->has('department_permissions')) {
                $user->departmentPermissions()->delete();

                foreach ($request->department_permissions as $permission) {
                    UserDepartmentPermission::create([
                        'user_id' => $user->id,
                        'department' => $permission['department'],
                        'can_view' => $permission['can_view'] ?? false,
                        'can_edit_status' => $permission['can_edit_status'] ?? false,
                        'can_create_tasks' => $permission['can_create_tasks'] ?? false,
                        'can_edit_all' => $permission['can_edit_all'] ?? false,
                    ]);
                }
            }
        });

        $user->load('departmentPermissions');

        return response()->json([
            'message' => 'User updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get users for dropdown (simplified list)
     */
    public function dropdown(Request $request): JsonResponse
    {
        $query = User::where('is_active', true)
            ->select('id', 'username', 'full_name', 'primary_department');

        if ($request->has('department')) {
            $query->where('primary_department', $request->department);
        }

        $users = $query->orderBy('full_name')->get();

        return response()->json([
            'users' => $users->map(fn($user) => [
                'id' => $user->id,
                'label' => $user->full_name,
                'username' => $user->username,
                'department' => $user->primary_department,
            ]),
        ]);
    }
}
