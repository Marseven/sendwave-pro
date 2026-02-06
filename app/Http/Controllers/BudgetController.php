<?php

namespace App\Http\Controllers;

use App\Models\SubAccount;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/budgets/status/{subAccountId}",
     *     tags={"Budget"},
     *     summary="Get budget status",
     *     description="Obtenir le statut du budget pour un sous-compte spécifique",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="subAccountId",
     *         in="path",
     *         required=false,
     *         description="ID du sous-compte",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut du budget",
     *         @OA\JsonContent(
     *             @OA\Property(property="sub_account", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sous-compte 1")
     *             ),
     *             @OA\Property(property="budget", type="object",
     *                 @OA\Property(property="has_budget", type="boolean", example=true),
     *                 @OA\Property(property="budget", type="number", example=50000),
     *                 @OA\Property(property="message", type="string", example="Budget actif")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sous-compte non trouvé")
     * )
     */
    public function status(Request $request, ?int $subAccountId = null)
    {
        $user = $request->user();

        if ($subAccountId) {
            $subAccount = SubAccount::where('parent_user_id', $user->id)
                ->findOrFail($subAccountId);
            $status = $this->budgetService->getBudgetStatus($subAccount);
        } else {
            $subAccount = null;
            $status = [
                'has_budget' => false,
                'budget' => null,
                'message' => 'Aucun sous-compte spécifié',
            ];
        }

        return response()->json([
            'sub_account' => $subAccount ? [
                'id' => $subAccount->id,
                'name' => $subAccount->name,
            ] : null,
            'budget' => $status,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/budgets/all",
     *     tags={"Budget"},
     *     summary="Get all budgets status",
     *     description="Obtenir le statut des budgets pour tous les sous-comptes de l'utilisateur",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des budgets",
     *         @OA\JsonContent(
     *             @OA\Property(property="budgets", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="sub_account", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Sous-compte 1"),
     *                         @OA\Property(property="email", type="string", example="sub@example.com")
     *                     ),
     *                     @OA\Property(property="budget", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function allStatus(Request $request)
    {
        $user = $request->user();

        $subAccounts = SubAccount::where('parent_user_id', $user->id)
            ->whereNotNull('monthly_budget')
            ->get();

        $budgets = $subAccounts->map(function ($subAccount) {
            $status = $this->budgetService->getBudgetStatus($subAccount);

            return [
                'sub_account' => [
                    'id' => $subAccount->id,
                    'name' => $subAccount->name,
                    'email' => $subAccount->email,
                ],
                'budget' => $status,
            ];
        });

        return response()->json(['budgets' => $budgets]);
    }

    /**
     * @OA\Put(
     *     path="/api/budgets/{subAccountId}",
     *     tags={"Budget"},
     *     summary="Update budget settings",
     *     description="Mettre à jour les paramètres de budget d'un sous-compte",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="subAccountId",
     *         in="path",
     *         required=true,
     *         description="ID du sous-compte",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="monthly_budget", type="number", nullable=true, example=100000),
     *             @OA\Property(property="budget_alert_threshold", type="number", nullable=true, minimum=0, maximum=100, example=80),
     *             @OA\Property(property="block_on_budget_exceeded", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Budget mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Budget mis à jour avec succès"),
     *             @OA\Property(property="sub_account", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sous-compte non trouvé"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $subAccountId)
    {
        $validated = $request->validate([
            'monthly_budget' => 'nullable|numeric|min:0',
            'budget_alert_threshold' => 'nullable|numeric|min:0|max:100',
            'block_on_budget_exceeded' => 'nullable|boolean',
        ]);

        $user = $request->user();

        $subAccount = SubAccount::where('parent_user_id', $user->id)
            ->findOrFail($subAccountId);

        $subAccount->update($validated);

        return response()->json([
            'message' => 'Budget mis à jour avec succès',
            'sub_account' => $subAccount->fresh(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/budgets/check-send",
     *     tags={"Budget"},
     *     summary="Check if send is allowed",
     *     description="Vérifier si un envoi est autorisé par rapport au budget avant l'envoi effectif",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"estimated_cost"},
     *             @OA\Property(property="sub_account_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="estimated_cost", type="number", minimum=0, example=500)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résultat de la vérification",
     *         @OA\JsonContent(
     *             @OA\Property(property="allowed", type="boolean", example=true),
     *             @OA\Property(property="remaining_budget", type="number", example=49500),
     *             @OA\Property(property="message", type="string", example="Envoi autorisé")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sous-compte non trouvé"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function checkSend(Request $request)
    {
        $validated = $request->validate([
            'sub_account_id' => 'nullable|integer',
            'estimated_cost' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $subAccountId = $validated['sub_account_id'] ?? null;

        if ($subAccountId) {
            $subAccount = SubAccount::where('parent_user_id', $user->id)
                ->findOrFail($subAccountId);
        } else {
            $subAccount = $user->getDefaultSubAccount();
        }

        if (!$subAccount) {
            return response()->json([
                'allowed' => false,
                'message' => 'Aucun sous-compte trouvé',
            ]);
        }

        $check = $this->budgetService->checkBudget($subAccount, $validated['estimated_cost']);

        return response()->json($check);
    }

    /**
     * @OA\Get(
     *     path="/api/budgets/history/{subAccountId}",
     *     tags={"Budget"},
     *     summary="Get budget usage history",
     *     description="Historique d'utilisation du budget par mois (12 derniers mois)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="subAccountId",
     *         in="path",
     *         required=false,
     *         description="ID du sous-compte (optionnel, si absent retourne l'historique global)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique du budget",
     *         @OA\JsonContent(
     *             @OA\Property(property="history", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="period", type="string", example="2026-01"),
     *                     @OA\Property(property="formatted", type="string", example="janvier 2026"),
     *                     @OA\Property(property="total_sms", type="integer", example=150),
     *                     @OA\Property(property="total_cost", type="number", example=3000)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Sous-compte non trouvé")
     * )
     */
    public function history(Request $request, ?int $subAccountId = null)
    {
        $user = $request->user();

        $query = \App\Models\SmsAnalytics::where('user_id', $user->id);

        if ($subAccountId) {
            // Vérifier que le sous-compte appartient à l'utilisateur
            SubAccount::where('parent_user_id', $user->id)
                ->findOrFail($subAccountId);

            $query->where('sub_account_id', $subAccountId);
        }

        $history = $query
            ->groupBy('period_key')
            ->selectRaw('period_key, COUNT(*) as total_sms, SUM(total_cost) as total_cost')
            ->orderByDesc('period_key')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return [
                    'period' => $row->period_key,
                    'formatted' => \Carbon\Carbon::createFromFormat('Y-m', $row->period_key)->translatedFormat('F Y'),
                    'total_sms' => $row->total_sms,
                    'total_cost' => (float) $row->total_cost,
                ];
            });

        return response()->json(['history' => $history]);
    }
}
