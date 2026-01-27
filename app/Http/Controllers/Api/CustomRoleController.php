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
