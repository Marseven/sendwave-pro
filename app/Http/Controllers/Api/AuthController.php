<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Authenticate user and return access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@sendwave.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="1|abcdefghijklmnopqrstuvwxyz"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="admin@sendwave.com"),
     *                 @OA\Property(property="role", type="string", example="admin"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les identifiants fournis sont incorrects.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUserWithPermissions($user)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Authentication"},
     *     summary="Register new user",
     *     description="Create a new user account and return access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", minLength=8)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // New users are created as Admin by default with default permissions
        $defaultRole = UserRole::ADMIN;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $defaultRole->value,
            'permissions' => $defaultRole->defaultPermissions(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUserWithPermissions($user)
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     description="Revoke current access token",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user",
     *     description="Get authenticated user information with permissions",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="role", type="string", example="admin"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(Request $request)
    {
        return response()->json($this->formatUserWithPermissions($request->user()));
    }

    /**
     * @OA\Get(
     *     path="/api/auth/permissions",
     *     tags={"Authentication"},
     *     summary="Get user permissions",
     *     description="Get current user's permissions and role info",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="role", type="string", example="admin"),
     *             @OA\Property(property="role_label", type="string", example="Administrateur"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_super_admin", type="boolean", example=false),
     *             @OA\Property(property="is_admin", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function permissions(Request $request)
    {
        $user = $request->user();
        $roleEnum = $user->getRoleEnum();

        return response()->json([
            'role' => $user->role,
            'role_label' => $roleEnum?->label() ?? $user->role,
            'permissions' => $user->getAllPermissions(),
            'is_super_admin' => $user->isSuperAdmin(),
            'is_admin' => $user->isAdmin(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/available-permissions",
     *     tags={"Authentication"},
     *     summary="Get all available permissions",
     *     description="Get list of all permissions grouped by category",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="permissions", type="object"),
     *             @OA\Property(property="roles", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function availablePermissions(Request $request)
    {
        return response()->json([
            'permissions' => Permission::groupedByCategory(),
            'roles' => collect(UserRole::cases())->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
                'level' => $role->level(),
                'default_permissions' => $role->defaultPermissions(),
            ])->keyBy('value'),
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->formatUserWithPermissions($user)
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'email_notifications' => 'nullable|boolean',
            'weekly_reports' => 'nullable|boolean',
            'campaign_alerts' => 'nullable|boolean',
        ]);

        // Mettre à jour le mot de passe si fourni
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Retirer password_confirmation de la mise à jour
        unset($validated['password_confirmation']);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'data' => $this->formatUserWithPermissions($user->fresh())
        ]);
    }

    /**
     * Format user data with permissions for API response
     */
    protected function formatUserWithPermissions(User $user): array
    {
        $roleEnum = $user->getRoleEnum();
        $customRole = $user->customRole;

        return [
            'id' => $user->id,
            'parent_id' => $user->parent_id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'company' => $user->company,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'role_label' => $roleEnum?->label() ?? $user->role,
            'custom_role_id' => $user->custom_role_id,
            'custom_role_name' => $customRole?->name,
            'permissions' => $user->getAllPermissions(),
            'status' => $user->status ?? 'active',
            'is_super_admin' => $user->isSuperAdmin(),
            'is_admin' => $user->isAdmin(),
            'is_agent' => $user->isAgent(),
            'can_manage_users' => $user->hasPermission('manage_sub_accounts'),
            'can_create_agents' => $user->canCreateUserWithRole(UserRole::AGENT),
            'email_notifications' => $user->email_notifications,
            'weekly_reports' => $user->weekly_reports,
            'campaign_alerts' => $user->campaign_alerts,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
