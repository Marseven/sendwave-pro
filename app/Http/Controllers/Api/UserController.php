<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomRole;
use App\Enums\UserRole;
use App\Enums\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get list of users manageable by current user
     *
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="List users manageable by current user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="account_id", in="query", required=false, @OA\Schema(type="integer"), description="Filter by account (SuperAdmin only)"),
     *     @OA\Parameter(name="role", in="query", required=false, @OA\Schema(type="string"), description="Filter by role"),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string"), description="Filter by status"),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), description="Search by name or email"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), description="Items per page"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $query = User::with('account:id,name');

        // Filter based on user's role
        if ($currentUser->isSuperAdmin()) {
            // SuperAdmin sees all users except themselves
            $query->where('id', '!=', $currentUser->id);
        } elseif ($currentUser->isAdmin()) {
            // Admin sees users in their account (excluding themselves)
            $query->where('account_id', $currentUser->account_id)
                  ->where('id', '!=', $currentUser->id);
        } else {
            // Agents cannot see other users
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir les utilisateurs.',
            ], 403);
        }

        // Filter by account (SuperAdmin only)
        if ($currentUser->isSuperAdmin() && $request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Apply filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['parent', 'customRole'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Create a new user
     *
     * @OA\Post(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "email", "password", "role"},
     *         @OA\Property(property="name", type="string", example="John Doe"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", minLength=8),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="company", type="string"),
     *         @OA\Property(property="role", type="string", enum={"super_admin", "admin", "agent"}),
     *         @OA\Property(property="account_id", type="integer"),
     *         @OA\Property(property="custom_role_id", type="integer"),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *     )),
     *     @OA\Response(response=201, description="User created", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();

        // Validate basic fields
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'role' => 'required|string|in:super_admin,admin,agent',
            'account_id' => 'nullable|exists:accounts,id',
            'custom_role_id' => 'nullable|exists:custom_roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        // Check if current user can create user with this role
        if (!$currentUser->canCreateUserWithRole($validated['role'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de créer un utilisateur avec ce rôle.',
            ], 403);
        }

        // Get permissions based on role or custom role
        $permissions = $validated['permissions'] ?? null;
        if (!$permissions) {
            if (isset($validated['custom_role_id'])) {
                $customRole = CustomRole::find($validated['custom_role_id']);
                $permissions = $customRole ? $customRole->permissions : [];
            } else {
                $roleEnum = UserRole::from($validated['role']);
                $permissions = $roleEnum->defaultPermissions();
            }
        }

        // Determine account_id
        $accountId = null;
        if ($currentUser->isSuperAdmin() && isset($validated['account_id'])) {
            // SuperAdmin can specify account
            $accountId = $validated['account_id'];
        } elseif ($currentUser->account_id) {
            // Admin creates users in their own account
            $accountId = $currentUser->account_id;
        }

        // Create user
        $user = User::create([
            'account_id' => $accountId,
            'parent_id' => $currentUser->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'role' => $validated['role'],
            'custom_role_id' => $validated['custom_role_id'] ?? null,
            'permissions' => $permissions,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user->load(['parent', 'customRole']),
        ], 201);
    }

    /**
     * Get a specific user
     *
     * @OA\Get(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Get a specific user by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::with(['parent', 'customRole', 'children'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Check if current user can view this user
        if (!$currentUser->canManage($user) && $currentUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir cet utilisateur.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update a user
     *
     * @OA\Put(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Update an existing user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", minLength=8),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="company", type="string"),
     *         @OA\Property(property="role", type="string", enum={"super_admin", "admin", "agent"}),
     *         @OA\Property(property="custom_role_id", type="integer"),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *         @OA\Property(property="status", type="string", enum={"active", "suspended", "pending"})
     *     )),
     *     @OA\Response(response=200, description="User updated", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Check if current user can manage this user
        if (!$currentUser->canManage($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier cet utilisateur.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'role' => 'sometimes|string|in:super_admin,admin,agent',
            'custom_role_id' => 'nullable|exists:custom_roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'status' => 'sometimes|string|in:active,suspended,pending',
        ]);

        // Check role change permissions
        if (isset($validated['role']) && $validated['role'] !== $user->role) {
            if (!$currentUser->canCreateUserWithRole($validated['role'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas la permission de changer le rôle vers ' . $validated['role'],
                ], 403);
            }
        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès.',
            'data' => $user->fresh()->load(['parent', 'customRole']),
        ]);
    }

    /**
     * Delete a user
     *
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Delete a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User deleted", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=400, description="User has sub-users"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        // Check if current user can manage this user
        if (!$currentUser->canManage($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de supprimer cet utilisateur.',
            ], 403);
        }

        // Cannot delete users with children
        if ($user->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un utilisateur qui a des sous-utilisateurs.',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    /**
     * Suspend a user
     *
     * @OA\Post(
     *     path="/api/users/{id}/suspend",
     *     tags={"Users"},
     *     summary="Suspend a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User suspended", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function suspend(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        if (!$currentUser->canManage($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de suspendre cet utilisateur.',
            ], 403);
        }

        $user->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur suspendu.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Activate a user
     *
     * @OA\Post(
     *     path="/api/users/{id}/activate",
     *     tags={"Users"},
     *     summary="Activate a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User activated", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function activate(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        if (!$currentUser->canManage($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission d\'activer cet utilisateur.',
            ], 403);
        }

        $user->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur activé.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Update user permissions
     *
     * @OA\Put(
     *     path="/api/users/{id}/permissions",
     *     tags={"Users"},
     *     summary="Update permissions for a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"permissions"},
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *     )),
     *     @OA\Response(response=200, description="Permissions updated", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updatePermissions(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.',
            ], 404);
        }

        if (!$currentUser->canManage($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier les permissions de cet utilisateur.',
            ], 403);
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        // Admin can only give permissions they have themselves
        if (!$currentUser->isSuperAdmin()) {
            $allowedPermissions = $currentUser->getAllPermissions();
            $requestedPermissions = $validated['permissions'];

            foreach ($requestedPermissions as $permission) {
                if (!in_array($permission, $allowedPermissions)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Vous n'avez pas la permission d'accorder: {$permission}",
                    ], 403);
                }
            }
        }

        $user->update([
            'permissions' => $validated['permissions'],
            'custom_role_id' => null, // Clear custom role when setting custom permissions
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions mises à jour.',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Get available roles that current user can assign
     *
     * @OA\Get(
     *     path="/api/users/available-roles",
     *     tags={"Users"},
     *     summary="Get roles the current user can assign",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object",
     *             @OA\Property(property="value", type="string"),
     *             @OA\Property(property="label", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="default_permissions", type="array", @OA\Items(type="string"))
     *         ))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function availableRoles(Request $request)
    {
        $currentUser = $request->user();
        $roles = [];

        foreach (UserRole::cases() as $role) {
            if ($currentUser->canCreateUserWithRole($role)) {
                $roles[] = [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                    'default_permissions' => $role->defaultPermissions(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get available permissions that current user can assign
     *
     * @OA\Get(
     *     path="/api/users/available-permissions",
     *     tags={"Users"},
     *     summary="Get permissions the current user can assign",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function availablePermissions(Request $request)
    {
        $currentUser = $request->user();

        // SuperAdmin can assign all permissions
        if ($currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => true,
                'data' => Permission::groupedByCategory(),
            ]);
        }

        // Others can only assign permissions they have
        $userPermissions = $currentUser->getAllPermissions();
        $grouped = Permission::groupedByCategory();

        // Filter to only include permissions the user has
        foreach ($grouped as $category => $permissions) {
            $grouped[$category] = array_filter($permissions, function ($p) use ($userPermissions) {
                return in_array($p['value'], $userPermissions);
            });
            $grouped[$category] = array_values($grouped[$category]);
            if (empty($grouped[$category])) {
                unset($grouped[$category]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Get all system roles (read-only view)
     *
     * @OA\Get(
     *     path="/api/users/system-roles",
     *     tags={"Users"},
     *     summary="Get all system roles (read-only)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object",
     *             @OA\Property(property="value", type="string"),
     *             @OA\Property(property="label", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="level", type="integer"),
     *             @OA\Property(property="default_permissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_system", type="boolean")
     *         ))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function systemRoles(Request $request)
    {
        $roles = [];

        foreach (UserRole::cases() as $role) {
            $roles[] = [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
                'level' => $role->level(),
                'default_permissions' => $role->defaultPermissions(),
                'is_system' => true,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get all system permissions (read-only view)
     *
     * @OA\Get(
     *     path="/api/users/system-permissions",
     *     tags={"Users"},
     *     summary="Get all system permissions (read-only)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function systemPermissions(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Permission::groupedByCategory(),
        ]);
    }
}
