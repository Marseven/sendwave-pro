<?php

namespace App\Services;

use App\Models\SubAccount;
use App\Models\SmsAnalytics;
use App\Events\BudgetAlertEvent;
use App\Events\BudgetExceededEvent;
use Illuminate\Support\Facades\Log;

class BudgetService
{
    /**
     * Vérifier le budget avant envoi
     */
    public function checkBudget(SubAccount $subAccount, float $estimatedCost): array
    {
        // Si pas de budget défini, autoriser
        if (!$subAccount->monthly_budget) {
            return [
                'allowed' => true,
                'has_budget' => false,
                'remaining' => null,
            ];
        }

        $currentPeriod = now()->format('Y-m');
        $spent = $this->getSpentAmount($subAccount->id, $currentPeriod);
        $remaining = $subAccount->monthly_budget - $spent;
        $percentUsed = ($spent / $subAccount->monthly_budget) * 100;

        // Vérifier si on dépasse le budget
        if ($remaining < $estimatedCost) {
            if ($subAccount->block_on_budget_exceeded) {
                event(new BudgetExceededEvent($subAccount, $spent, $subAccount->monthly_budget));

                Log::warning("Budget exceeded for sub_account {$subAccount->id}", [
                    'spent' => $spent,
                    'budget' => $subAccount->monthly_budget,
                    'estimated_cost' => $estimatedCost,
                ]);

                return [
                    'allowed' => false,
                    'has_budget' => true,
                    'remaining' => $remaining,
                    'percent_used' => round($percentUsed, 2),
                    'message' => 'Budget mensuel dépassé. Envoi bloqué.',
                    'error_code' => 'BUDGET_EXCEEDED',
                ];
            }

            // Budget dépassé mais non bloquant - juste alerter
            event(new BudgetExceededEvent($subAccount, $spent, $subAccount->monthly_budget));
        }

        // Vérifier le seuil d'alerte
        if ($percentUsed >= $subAccount->budget_alert_threshold) {
            event(new BudgetAlertEvent($subAccount, $percentUsed));

            Log::info("Budget alert for sub_account {$subAccount->id}", [
                'percent_used' => $percentUsed,
                'threshold' => $subAccount->budget_alert_threshold,
            ]);
        }

        return [
            'allowed' => true,
            'has_budget' => true,
            'remaining' => $remaining,
            'percent_used' => round($percentUsed, 2),
            'warning' => $percentUsed >= $subAccount->budget_alert_threshold
                ? "Attention: {$percentUsed}% du budget utilisé"
                : null,
        ];
    }

    /**
     * Obtenir le statut complet du budget
     */
    public function getBudgetStatus(SubAccount $subAccount): array
    {
        if (!$subAccount->monthly_budget) {
            return [
                'has_budget' => false,
                'budget' => null,
                'spent' => 0,
                'remaining' => null,
                'percent_used' => 0,
            ];
        }

        $currentPeriod = now()->format('Y-m');
        $spent = $this->getSpentAmount($subAccount->id, $currentPeriod);
        $remaining = $subAccount->monthly_budget - $spent;
        $percentUsed = ($spent / $subAccount->monthly_budget) * 100;

        return [
            'has_budget' => true,
            'budget' => $subAccount->monthly_budget,
            'spent' => round($spent, 2),
            'remaining' => round($remaining, 2),
            'percent_used' => round($percentUsed, 2),
            'alert_threshold' => $subAccount->budget_alert_threshold,
            'block_on_exceeded' => $subAccount->block_on_budget_exceeded,
            'is_warning' => $percentUsed >= $subAccount->budget_alert_threshold,
            'is_exceeded' => $remaining <= 0,
            'currency' => 'FCFA',
            'period' => $currentPeriod,
        ];
    }

    /**
     * Obtenir le montant dépensé pour une période
     */
    public function getSpentAmount(int $subAccountId, string $period): float
    {
        return SmsAnalytics::bySubAccount($subAccountId)
            ->forPeriod($period)
            ->sum('total_cost');
    }

    /**
     * Mettre à jour le budget d'un sous-compte
     */
    public function updateBudget(SubAccount $subAccount, array $data): SubAccount
    {
        $subAccount->update([
            'monthly_budget' => $data['monthly_budget'] ?? $subAccount->monthly_budget,
            'budget_alert_threshold' => $data['budget_alert_threshold'] ?? $subAccount->budget_alert_threshold,
            'block_on_budget_exceeded' => $data['block_on_budget_exceeded'] ?? $subAccount->block_on_budget_exceeded,
        ]);

        return $subAccount->fresh();
    }

    /**
     * Obtenir les sous-comptes proches du seuil d'alerte
     */
    public function getSubAccountsNearThreshold(int $userId): array
    {
        $currentPeriod = now()->format('Y-m');

        return SubAccount::where('user_id', $userId)
            ->whereNotNull('monthly_budget')
            ->get()
            ->filter(function ($subAccount) use ($currentPeriod) {
                $spent = $this->getSpentAmount($subAccount->id, $currentPeriod);
                $percentUsed = ($spent / $subAccount->monthly_budget) * 100;
                return $percentUsed >= ($subAccount->budget_alert_threshold - 10); // 10% avant le seuil
            })
            ->map(fn($subAccount) => [
                'id' => $subAccount->id,
                'name' => $subAccount->name,
                'status' => $this->getBudgetStatus($subAccount),
            ])
            ->values()
            ->toArray();
    }
}
