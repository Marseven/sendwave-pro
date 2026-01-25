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
     * Obtenir le statut du budget pour un sous-compte
     */
    public function status(Request $request, ?int $subAccountId = null)
    {
        $user = $request->user();

        if ($subAccountId) {
            $subAccount = SubAccount::where('parent_user_id', $user->id)
                ->findOrFail($subAccountId);
        } else {
            $subAccount = null;
        }

        $status = $this->budgetService->getBudgetStatus($user->id, $subAccountId);

        return response()->json([
            'sub_account' => $subAccount ? [
                'id' => $subAccount->id,
                'name' => $subAccount->name,
            ] : null,
            'budget' => $status,
        ]);
    }

    /**
     * Obtenir le statut des budgets pour tous les sous-comptes
     */
    public function allStatus(Request $request)
    {
        $user = $request->user();

        $subAccounts = SubAccount::where('parent_user_id', $user->id)
            ->whereNotNull('monthly_budget')
            ->get();

        $budgets = $subAccounts->map(function ($subAccount) use ($user) {
            $status = $this->budgetService->getBudgetStatus($user->id, $subAccount->id);

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
     * Mettre à jour les paramètres de budget d'un sous-compte
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
     * Vérifier si un envoi est autorisé (avant envoi)
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
            // Vérifier que le sous-compte appartient à l'utilisateur
            SubAccount::where('parent_user_id', $user->id)
                ->findOrFail($subAccountId);
        }

        $check = $this->budgetService->checkBudget(
            $user->id,
            $subAccountId,
            $validated['estimated_cost']
        );

        return response()->json($check);
    }

    /**
     * Historique d'utilisation du budget par mois
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
