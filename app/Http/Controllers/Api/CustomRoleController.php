<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomRole;
use App\Enums\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomRoleController extends Controller
{
    /**
     * Get list of custom roles
     *
     * @OA\Get(
     *     path="/api/custom-roles",
     *     tags={"Custom Roles"},
     *     summary="List all custom roles (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Only SuperAdmin can view/manage custom roles
        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut gérer les rôles personnalisés.',
            ], 403);
        }

        $roles = CustomRole::with('creator')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Create a new custom role
     *
     * @OA\Post(
     *     path="/api/custom-roles",
     *     tags={"Custom Roles"},
     *     summary="Create a new custom role (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "permissions"},
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="slug", type="string", maxLength=255),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *     )),
     *     @OA\Response(response=201, description="Custom role created", @OA\JsonContent(
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

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut créer des rôles personnalisés.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:custom_roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        // Generate slug if not provided
        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Ensure slug is unique
        $baseSlug = $slug;
        $counter = 1;
        while (CustomRole::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $role = CustomRole::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'],
            'created_by' => $currentUser->id,
            'is_system' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle personnalisé créé avec succès.',
            'data' => $role->load('creator'),
        ], 201);
    }

    /**
     * Get a specific custom role
     *
     * @OA\Get(
     *     path="/api/custom-roles/{id}",
     *     tags={"Custom Roles"},
     *     summary="Get a specific custom role by ID (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Custom role not found")
     * )
     */
    public function show(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut voir les rôles personnalisés.',
            ], 403);
        }

        $role = CustomRole::with(['creator', 'users'])->find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * Update a custom role
     *
     * @OA\Put(
     *     path="/api/custom-roles/{id}",
     *     tags={"Custom Roles"},
     *     summary="Update an existing custom role (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="slug", type="string", maxLength=255),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *     )),
     *     @OA\Response(response=200, description="Custom role updated", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - system roles cannot be modified"),
     *     @OA\Response(response=404, description="Custom role not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut modifier les rôles personnalisés.',
            ], 403);
        }

        $role = CustomRole::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        // Cannot modify system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Les rôles système ne peuvent pas être modifiés.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('custom_roles')->ignore($role->id)],
            'description' => 'nullable|string',
            'permissions' => 'sometimes|array|min:1',
            'permissions.*' => 'string',
        ]);

        $role->update($validated);

        // Update permissions for all users with this role
        if (isset($validated['permissions'])) {
            $role->users()->update(['permissions' => $validated['permissions']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rôle mis à jour avec succès.',
            'data' => $role->fresh()->load('creator'),
        ]);
    }

    /**
     * Delete a custom role
     *
     * @OA\Delete(
     *     path="/api/custom-roles/{id}",
     *     tags={"Custom Roles"},
     *     summary="Delete a custom role (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Custom role deleted", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=400, description="Role is in use by users"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - system roles cannot be deleted"),
     *     @OA\Response(response=404, description="Custom role not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut supprimer les rôles personnalisés.',
            ], 403);
        }

        $role = CustomRole::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        // Cannot delete system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Les rôles système ne peuvent pas être supprimés.',
            ], 403);
        }

        // Check if role is in use
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rôle est assigné à des utilisateurs et ne peut pas être supprimé.',
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rôle supprimé avec succès.',
        ]);
    }

    /**
     * Duplicate a custom role
     *
     * @OA\Post(
     *     path="/api/custom-roles/{id}/duplicate",
     *     tags={"Custom Roles"},
     *     summary="Duplicate an existing custom role (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Custom role duplicated", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Custom role not found")
     * )
     */
    public function duplicate(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut dupliquer les rôles.',
            ], 403);
        }

        $role = CustomRole::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle non trouvé.',
            ], 404);
        }

        // Generate unique name and slug
        $baseName = $role->name . ' (Copie)';
        $baseSlug = $role->slug . '-copy';
        $counter = 1;

        while (CustomRole::where('slug', $baseSlug)->exists()) {
            $baseSlug = $role->slug . '-copy-' . $counter++;
        }

        $newRole = CustomRole::create([
            'name' => $baseName,
            'slug' => $baseSlug,
            'description' => $role->description,
            'permissions' => $role->permissions,
            'created_by' => $currentUser->id,
            'is_system' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle dupliqué avec succès.',
            'data' => $newRole->load('creator'),
        ], 201);
    }

    /**
     * Get available permissions for role creation
     *
     * @OA\Get(
     *     path="/api/custom-roles/permissions",
     *     tags={"Custom Roles"},
     *     summary="Get available permissions for role creation (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function permissions(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut voir les permissions disponibles.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => Permission::groupedByCategory(),
        ]);
    }
}
