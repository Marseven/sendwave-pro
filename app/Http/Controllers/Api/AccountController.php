<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\SubAccount;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\SubAccountRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Get list of accounts
     *
     * @OA\Get(
     *     path="/api/accounts",
     *     tags={"Accounts"},
     *     summary="List all accounts (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), description="Search by name, email or company ID"),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string"), description="Filter by status"),
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

        // Only SuperAdmin can view all accounts
        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $query = Account::withCount('users');

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_id', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $accounts = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Create a new account
     *
     * @OA\Post(
     *     path="/api/accounts",
     *     tags={"Accounts"},
     *     summary="Create a new account with admin user (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "email", "admin_name", "admin_email", "admin_password"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="company_id", type="string"),
     *         @OA\Property(property="address", type="string"),
     *         @OA\Property(property="city", type="string"),
     *         @OA\Property(property="country", type="string", maxLength=2),
     *         @OA\Property(property="sms_credits", type="number"),
     *         @OA\Property(property="monthly_budget", type="number"),
     *         @OA\Property(property="budget_alert_threshold", type="number"),
     *         @OA\Property(property="block_on_budget_exceeded", type="boolean"),
     *         @OA\Property(property="notes", type="string"),
     *         @OA\Property(property="admin_name", type="string"),
     *         @OA\Property(property="admin_email", type="string", format="email"),
     *         @OA\Property(property="admin_password", type="string", minLength=8)
     *     )),
     *     @OA\Response(response=201, description="Account created", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut créer des comptes.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:accounts,email',
            'phone' => 'nullable|string|max:20',
            'company_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
            'sms_credits' => 'nullable|numeric|min:0',
            'monthly_budget' => 'nullable|numeric|min:0',
            'budget_alert_threshold' => 'nullable|numeric|min:0|max:100',
            'block_on_budget_exceeded' => 'nullable|boolean',
            'notes' => 'nullable|string',
            // Admin user for the account
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        try {
            DB::beginTransaction();

            // Create the account
            $account = Account::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'company_id' => $validated['company_id'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? 'GA',
                'sms_credits' => $validated['sms_credits'] ?? 0,
                'monthly_budget' => $validated['monthly_budget'] ?? null,
                'budget_alert_threshold' => $validated['budget_alert_threshold'] ?? 80,
                'block_on_budget_exceeded' => $validated['block_on_budget_exceeded'] ?? false,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
            ]);

            // Create the admin user for this account
            $adminUser = User::create([
                'account_id' => $account->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'role' => UserRole::ADMIN->value,
                'permissions' => UserRole::ADMIN->defaultPermissions(),
                'status' => 'active',
            ]);

            // Auto-create default SubAccount with initial credits
            $defaultSub = SubAccount::create([
                'account_id'     => $account->id,
                'parent_user_id' => $adminUser->id,
                'name'           => 'Compte principal',
                'email'          => "default.{$account->id}@internal.sendwave",
                'password'       => Hash::make(Str::random(32)),
                'role'           => 'admin',
                'status'         => 'active',
                'is_default'     => true,
                'sms_credits'    => $validated['sms_credits'] ?? 0,
                'permissions'    => SubAccountRole::ADMIN->defaultPermissions(),
            ]);

            // Sync account sms_credits from sub-accounts
            $defaultSub->syncAccountCredits();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès.',
                'data' => $account->load('users'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific account
     *
     * @OA\Get(
     *     path="/api/accounts/{id}",
     *     tags={"Accounts"},
     *     summary="Get a specific account by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function show(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            // Check if user belongs to this account
            if ($currentUser->account_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        }

        $account = Account::with(['users' => function ($q) {
            $q->select('id', 'account_id', 'name', 'email', 'role', 'status', 'created_at');
        }])->withCount('users')->find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $account,
        ]);
    }

    /**
     * Update an account
     *
     * @OA\Put(
     *     path="/api/accounts/{id}",
     *     tags={"Accounts"},
     *     summary="Update an existing account (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="company_id", type="string"),
     *         @OA\Property(property="address", type="string"),
     *         @OA\Property(property="city", type="string"),
     *         @OA\Property(property="country", type="string", maxLength=2),
     *         @OA\Property(property="monthly_budget", type="number"),
     *         @OA\Property(property="budget_alert_threshold", type="number"),
     *         @OA\Property(property="block_on_budget_exceeded", type="boolean"),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="Account updated", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut modifier les comptes.',
            ], 403);
        }

        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:accounts,email,' . $account->id,
            'phone' => 'nullable|string|max:20',
            'company_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
            'monthly_budget' => 'nullable|numeric|min:0',
            'budget_alert_threshold' => 'nullable|numeric|min:0|max:100',
            'block_on_budget_exceeded' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Compte mis à jour avec succès.',
            'data' => $account->fresh()->loadCount('users'),
        ]);
    }

    /**
     * Delete an account
     *
     * @OA\Delete(
     *     path="/api/accounts/{id}",
     *     tags={"Accounts"},
     *     summary="Delete an account (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Account deleted", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=400, description="Account has users"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut supprimer les comptes.',
            ], 403);
        }

        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        // Check if account has users
        if ($account->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte a des utilisateurs. Supprimez d\'abord les utilisateurs.',
            ], 400);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compte supprimé avec succès.',
        ]);
    }

    /**
     * Add credits to an account
     *
     * @OA\Post(
     *     path="/api/accounts/{id}/credits",
     *     tags={"Accounts"},
     *     summary="Add SMS credits to an account (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"amount"},
     *         @OA\Property(property="amount", type="number", minimum=0.01),
     *         @OA\Property(property="note", type="string", maxLength=500)
     *     )),
     *     @OA\Response(response=200, description="Credits added", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="new_balance", type="number")
     *         )
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function addCredits(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut ajouter des crédits.',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:500',
        ]);

        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        $account->addCredits($validated['amount']);

        return response()->json([
            'success' => true,
            'message' => "{$validated['amount']} crédits ajoutés avec succès.",
            'data' => [
                'new_balance' => $account->fresh()->sms_credits,
            ],
        ]);
    }

    /**
     * Suspend an account
     *
     * @OA\Post(
     *     path="/api/accounts/{id}/suspend",
     *     tags={"Accounts"},
     *     summary="Suspend an account and all its users (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Account suspended", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function suspend(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut suspendre les comptes.',
            ], 403);
        }

        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        $account->suspend();

        // Also suspend all users of this account
        $account->users()->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => 'Compte suspendu avec succès.',
            'data' => $account->fresh(),
        ]);
    }

    /**
     * Activate an account
     *
     * @OA\Post(
     *     path="/api/accounts/{id}/activate",
     *     tags={"Accounts"},
     *     summary="Activate an account and all its users (SuperAdmin only)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Account activated", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function activate(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le Super Administrateur peut activer les comptes.',
            ], 403);
        }

        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        $account->activate();

        // Also activate all users of this account
        $account->users()->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Compte activé avec succès.',
            'data' => $account->fresh(),
        ]);
    }

    /**
     * Get account statistics
     *
     * @OA\Get(
     *     path="/api/accounts/{id}/stats",
     *     tags={"Accounts"},
     *     summary="Get statistics for an account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="sms_credits", type="number"),
     *             @OA\Property(property="monthly_budget", type="number"),
     *             @OA\Property(property="budget_used", type="number"),
     *             @OA\Property(property="budget_usage_percent", type="number"),
     *             @OA\Property(property="sms_sent_total", type="integer"),
     *             @OA\Property(property="sms_sent_month", type="integer"),
     *             @OA\Property(property="campaigns_count", type="integer"),
     *             @OA\Property(property="contacts_count", type="integer"),
     *             @OA\Property(property="users_count", type="integer"),
     *             @OA\Property(property="last_activity_at", type="string", format="date-time")
     *         )
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function stats(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser->isSuperAdmin()) {
            if ($currentUser->account_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        }

        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        // Refresh stats
        $account->updateStats();

        return response()->json([
            'success' => true,
            'data' => [
                'sms_credits' => $account->sms_credits,
                'monthly_budget' => $account->monthly_budget,
                'budget_used' => $account->budget_used,
                'budget_usage_percent' => $account->budget_usage_percent,
                'sms_sent_total' => $account->sms_sent_total,
                'sms_sent_month' => $account->sms_sent_month,
                'campaigns_count' => $account->campaigns_count,
                'contacts_count' => $account->contacts_count,
                'users_count' => $account->users()->count(),
                'last_activity_at' => $account->last_activity_at,
            ],
        ]);
    }

    /**
     * Get users of an account
     *
     * @OA\Get(
     *     path="/api/accounts/{id}/users",
     *     tags={"Accounts"},
     *     summary="Get all users belonging to an account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Account not found")
     * )
     */
    public function users(Request $request, $id)
    {
        $currentUser = $request->user();

        // SuperAdmin can see all, Admin can see their own account users
        if (!$currentUser->isSuperAdmin()) {
            if (!$currentUser->isAdmin() || $currentUser->account_id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
        }

        $account = Account::find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé.',
            ], 404);
        }

        $users = $account->users()
            ->select('id', 'account_id', 'name', 'email', 'role', 'status', 'created_at', 'updated_at')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
