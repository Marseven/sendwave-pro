<?php

namespace App\Http\Controllers;

use App\Models\SubAccount;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SubAccountController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Liste tous les sous-comptes de l'utilisateur connecté
     *
     * @OA\Get(
     *     path="/api/sub-accounts",
     *     tags={"Sub-Accounts"},
     *     summary="List all sub-accounts of the authenticated user",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="sms_credit_limit", type="integer"),
     *             @OA\Property(property="sms_used", type="integer"),
     *             @OA\Property(property="remaining_credits", type="integer"),
     *             @OA\Property(property="last_connection", type="string", format="date-time"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         ))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $subAccounts = SubAccount::byParent($userId)
            ->select('id', 'account_id', 'name', 'email', 'role', 'status', 'is_default', 'sms_credits', 'budget_used', 'sms_credit_limit', 'sms_used', 'monthly_budget', 'last_connection', 'created_at')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_id' => $account->account_id,
                    'name' => $account->name,
                    'email' => $account->email,
                    'role' => $account->role,
                    'status' => $account->status,
                    'is_default' => $account->is_default,
                    'sms_credits' => (float) $account->sms_credits,
                    'budget_used' => (float) $account->budget_used,
                    'monthly_budget' => $account->monthly_budget ? (float) $account->monthly_budget : null,
                    'sms_credit_limit' => $account->sms_credit_limit,
                    'sms_used' => $account->sms_used,
                    'remaining_credits' => $account->remaining_credits,
                    'last_connection' => $account->last_connection?->format('Y-m-d H:i:s'),
                    'created_at' => $account->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'message' => 'Liste des sous-comptes',
            'data' => $subAccounts
        ]);
    }

    /**
     * Créer un nouveau sous-compte
     *
     * @OA\Post(
     *     path="/api/sub-accounts",
     *     tags={"Sub-Accounts"},
     *     summary="Create a new sub-account",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "email", "password", "role"},
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", minLength=8),
     *         @OA\Property(property="role", type="string", enum={"admin", "manager", "sender", "viewer"}),
     *         @OA\Property(property="sms_credit_limit", type="integer", minimum=0)
     *     )),
     *     @OA\Response(response=201, description="Sub-account created", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:sub_accounts,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager,sender,viewer',
            'sms_credits' => 'nullable|numeric|min:0',
            'monthly_budget' => 'nullable|numeric|min:0',
            'sms_credit_limit' => 'nullable|integer|min:0',
        ]);

        try {
            $subAccount = SubAccount::create([
                'parent_user_id' => $request->user()->id,
                'account_id' => $request->user()->account_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'status' => 'active',
                'sms_credits' => $validated['sms_credits'] ?? 0,
                'monthly_budget' => $validated['monthly_budget'] ?? null,
                'sms_credit_limit' => $validated['sms_credit_limit'] ?? null,
                'sms_used' => 0,
                'permissions' => (new SubAccount(['role' => $validated['role']]))->getDefaultPermissions(),
            ]);

            // Sync account total credits
            $subAccount->syncAccountCredits();

            Log::info('Sub-account created', [
                'parent_user_id' => $request->user()->id,
                'sub_account_id' => $subAccount->id,
                'email' => $subAccount->email,
                'role' => $subAccount->role,
            ]);

            // Trigger webhook for sub_account.created
            $this->webhookService->trigger('sub_account.created', $request->user()->id, [
                'sub_account_id' => $subAccount->id,
                'name' => $subAccount->name,
                'email' => $subAccount->email,
                'role' => $subAccount->role,
            ]);

            return response()->json([
                'message' => 'Sous-compte créé avec succès',
                'data' => [
                    'id' => $subAccount->id,
                    'account_id' => $subAccount->account_id,
                    'name' => $subAccount->name,
                    'email' => $subAccount->email,
                    'role' => $subAccount->role,
                    'is_default' => $subAccount->is_default,
                    'sms_credits' => (float) $subAccount->sms_credits,
                    'budget_used' => (float) $subAccount->budget_used,
                    'permissions' => $subAccount->permissions,
                    'sms_credit_limit' => $subAccount->sms_credit_limit,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create sub-account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du sous-compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un sous-compte spécifique
     *
     * @OA\Get(
     *     path="/api/sub-accounts/{id}",
     *     tags={"Sub-Accounts"},
     *     summary="Get a specific sub-account by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sub-account not found")
     * )
     */
    public function show(Request $request, int $id)
    {
        $subAccount = SubAccount::byParent($request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'message' => 'Détails du sous-compte',
            'data' => [
                'id' => $subAccount->id,
                'account_id' => $subAccount->account_id,
                'name' => $subAccount->name,
                'email' => $subAccount->email,
                'role' => $subAccount->role,
                'status' => $subAccount->status,
                'is_default' => $subAccount->is_default,
                'sms_credits' => (float) $subAccount->sms_credits,
                'budget_used' => (float) $subAccount->budget_used,
                'permissions' => $subAccount->permissions,
                'sms_credit_limit' => $subAccount->sms_credit_limit,
                'sms_used' => $subAccount->sms_used,
                'remaining_credits' => $subAccount->remaining_credits,
                'monthly_budget' => $subAccount->monthly_budget ? (float) $subAccount->monthly_budget : null,
                'last_connection' => $subAccount->last_connection?->format('Y-m-d H:i:s'),
                'created_at' => $subAccount->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Mettre à jour un sous-compte
     *
     * @OA\Put(
     *     path="/api/sub-accounts/{id}",
     *     tags={"Sub-Accounts"},
     *     summary="Update an existing sub-account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", minLength=8),
     *         @OA\Property(property="role", type="string", enum={"admin", "manager", "sender", "viewer"}),
     *         @OA\Property(property="status", type="string", enum={"active", "suspended", "inactive"}),
     *         @OA\Property(property="sms_credit_limit", type="integer", minimum=0)
     *     )),
     *     @OA\Response(response=200, description="Sub-account updated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sub-account not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, int $id)
    {
        $subAccount = SubAccount::byParent($request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('sub_accounts')->ignore($id)],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,manager,sender,viewer',
            'status' => 'sometimes|in:active,suspended,inactive',
            'sms_credit_limit' => 'nullable|integer|min:0',
        ]);

        try {
            $updateData = collect($validated)->except(['password'])->toArray();

            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            // Update default permissions if role changed
            if (isset($validated['role']) && $validated['role'] !== $subAccount->role) {
                $updateData['permissions'] = (new SubAccount(['role' => $validated['role']]))->getDefaultPermissions();
            }

            $subAccount->update($updateData);

            Log::info('Sub-account updated', [
                'sub_account_id' => $subAccount->id,
                'updated_fields' => array_keys($validated),
            ]);

            return response()->json([
                'message' => 'Sous-compte mis à jour avec succès',
                'data' => [
                    'id' => $subAccount->id,
                    'name' => $subAccount->name,
                    'email' => $subAccount->email,
                    'role' => $subAccount->role,
                    'status' => $subAccount->status,
                    'permissions' => $subAccount->permissions,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update sub-account', [
                'sub_account_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un sous-compte
     *
     * @OA\Delete(
     *     path="/api/sub-accounts/{id}",
     *     tags={"Sub-Accounts"},
     *     summary="Delete a sub-account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub-account deleted", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sub-account not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy(Request $request, int $id)
    {
        $subAccount = SubAccount::byParent($request->user()->id)
            ->findOrFail($id);

        if ($subAccount->is_default) {
            return response()->json([
                'message' => 'Impossible de supprimer le sous-compte principal.',
            ], 400);
        }

        try {
            $subAccount->delete();

            Log::info('Sub-account deleted', [
                'sub_account_id' => $id,
                'email' => $subAccount->email,
            ]);

            return response()->json([
                'message' => 'Sous-compte supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete sub-account', [
                'sub_account_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter des crédits SMS
     *
     * @OA\Post(
     *     path="/api/sub-accounts/{id}/credits",
     *     tags={"Sub-Accounts"},
     *     summary="Add SMS credits to a sub-account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"amount"},
     *         @OA\Property(property="amount", type="integer", minimum=1)
     *     )),
     *     @OA\Response(response=200, description="Credits added", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="sms_credit_limit", type="integer"),
     *             @OA\Property(property="sms_used", type="integer"),
     *             @OA\Property(property="remaining_credits", type="integer")
     *         )
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sub-account not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function addCredits(Request $request, int $id)
    {
        $subAccount = SubAccount::byParent($request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $subAccount->addCreditsFcfa($validated['amount']);

            Log::info('Credits (FCFA) added to sub-account', [
                'sub_account_id' => $id,
                'amount' => $validated['amount'],
                'new_balance' => $subAccount->fresh()->sms_credits,
            ]);

            $subAccount->refresh();

            return response()->json([
                'message' => 'Crédits ajoutés avec succès',
                'data' => [
                    'sms_credits' => (float) $subAccount->sms_credits,
                    'budget_used' => (float) $subAccount->budget_used,
                    'sms_credit_limit' => $subAccount->sms_credit_limit,
                    'sms_used' => $subAccount->sms_used,
                    'remaining_credits' => $subAccount->remaining_credits,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add credits', [
                'sub_account_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'ajout des crédits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour les permissions
     *
     * @OA\Post(
     *     path="/api/sub-accounts/{id}/permissions",
     *     tags={"Sub-Accounts"},
     *     summary="Update permissions for a sub-account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"permissions"},
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string", enum={
     *             "send_sms", "view_history", "manage_contacts", "manage_groups",
     *             "create_campaigns", "view_analytics", "manage_templates", "export_data"
     *         }))
     *     )),
     *     @OA\Response(response=200, description="Permissions updated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sub-account not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function updatePermissions(Request $request, int $id)
    {
        $subAccount = SubAccount::byParent($request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:send_sms,view_history,manage_contacts,manage_groups,create_campaigns,view_analytics,manage_templates,export_data',
        ]);

        try {
            $subAccount->update(['permissions' => $validated['permissions']]);

            Log::info('Permissions updated for sub-account', [
                'sub_account_id' => $id,
                'new_permissions' => $validated['permissions'],
            ]);

            return response()->json([
                'message' => 'Permissions mises à jour avec succès',
                'data' => [
                    'permissions' => $subAccount->permissions,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update permissions', [
                'sub_account_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour des permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspendre un sous-compte
     *
     * @OA\Post(
     *     path="/api/sub-accounts/{id}/suspend",
     *     tags={"Sub-Accounts"},
     *     summary="Suspend a sub-account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub-account suspended", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sub-account not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function suspend(Request $request, int $id)
    {
        $subAccount = SubAccount::byParent($request->user()->id)
            ->findOrFail($id);

        try {
            $subAccount->update(['status' => 'suspended']);

            Log::info('Sub-account suspended', ['sub_account_id' => $id]);

            // Trigger webhook for sub_account.suspended
            $this->webhookService->trigger('sub_account.suspended', $request->user()->id, [
                'sub_account_id' => $subAccount->id,
                'name' => $subAccount->name,
                'email' => $subAccount->email,
            ]);

            return response()->json([
                'message' => 'Sous-compte suspendu avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suspension',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer un sous-compte
     *
     * @OA\Post(
     *     path="/api/sub-accounts/{id}/activate",
     *     tags={"Sub-Accounts"},
     *     summary="Activate a sub-account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sub-account activated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sub-account not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function activate(Request $request, int $id)
    {
        $subAccount = SubAccount::byParent($request->user()->id)
            ->findOrFail($id);

        try {
            $subAccount->update(['status' => 'active']);

            Log::info('Sub-account activated', ['sub_account_id' => $id]);

            return response()->json([
                'message' => 'Sous-compte activé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'activation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transférer des crédits entre sous-comptes
     */
    public function transferCredits(Request $request)
    {
        $validated = $request->validate([
            'from_sub_account_id' => 'required|integer|exists:sub_accounts,id',
            'to_sub_account_id' => 'required|integer|exists:sub_accounts,id|different:from_sub_account_id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $userId = $request->user()->id;
        $accountId = $request->user()->account_id;

        $from = SubAccount::byParent($userId)->findOrFail($validated['from_sub_account_id']);
        $to = SubAccount::byParent($userId)->findOrFail($validated['to_sub_account_id']);

        // Verify both belong to the same account
        if ($from->account_id !== $to->account_id) {
            return response()->json([
                'message' => 'Les deux sous-comptes doivent appartenir au même compte.',
            ], 400);
        }

        // Check sufficient balance
        if ((float) $from->sms_credits < $validated['amount']) {
            return response()->json([
                'message' => 'Solde insuffisant sur le sous-compte source.',
                'data' => [
                    'available' => (float) $from->sms_credits,
                    'requested' => $validated['amount'],
                ],
            ], 400);
        }

        try {
            DB::transaction(function () use ($from, $to, $validated) {
                $from->decrement('sms_credits', $validated['amount']);
                $to->increment('sms_credits', $validated['amount']);
            });

            // No need to sync account credits — total remains the same

            Log::info('Credits transferred between sub-accounts', [
                'from' => $from->id,
                'to' => $to->id,
                'amount' => $validated['amount'],
            ]);

            return response()->json([
                'message' => 'Transfert effectué avec succès',
                'data' => [
                    'from' => [
                        'id' => $from->id,
                        'name' => $from->name,
                        'sms_credits' => (float) $from->fresh()->sms_credits,
                    ],
                    'to' => [
                        'id' => $to->id,
                        'name' => $to->name,
                        'sms_credits' => (float) $to->fresh()->sms_credits,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to transfer credits', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors du transfert de crédits',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Connexion pour sub-account
     *
     * @OA\Post(
     *     path="/api/sub-accounts/login",
     *     tags={"Sub-Accounts"},
     *     summary="Login as a sub-account (public, no authentication required)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"email", "password"},
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string")
     *     )),
     *     @OA\Response(response=200, description="Login successful", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="sub_account", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="remaining_credits", type="integer")
     *             )
     *         )
     *     )),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Account suspended or inactive"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $subAccount = SubAccount::where('email', $validated['email'])->first();

        if (!$subAccount || !Hash::check($validated['password'], $subAccount->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects'
            ], 401);
        }

        if ($subAccount->status !== 'active') {
            return response()->json([
                'message' => 'Compte suspendu ou inactif'
            ], 403);
        }

        // Update last connection
        $subAccount->update(['last_connection' => now()]);

        // Create token
        $token = $subAccount->createToken('sub-account-token')->plainTextToken;

        Log::info('Sub-account logged in', [
            'sub_account_id' => $subAccount->id,
            'email' => $subAccount->email,
        ]);

        return response()->json([
            'message' => 'Connexion réussie',
            'data' => [
                'token' => $token,
                'sub_account' => [
                    'id' => $subAccount->id,
                    'name' => $subAccount->name,
                    'email' => $subAccount->email,
                    'role' => $subAccount->role,
                    'permissions' => $subAccount->permissions,
                    'remaining_credits' => $subAccount->remaining_credits,
                ]
            ]
        ]);
    }
}
