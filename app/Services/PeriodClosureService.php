<?php

namespace App\Services;

use App\Models\SmsAnalytics;
use App\Models\PeriodClosure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodClosureService
{
    /**
     * Clôturer une période pour un utilisateur
     */
    public function closePeriod(User $user, string $periodKey): PeriodClosure
    {
        return DB::transaction(function () use ($user, $periodKey) {
            // Vérifier si déjà clôturé
            $existingClosure = PeriodClosure::byUser($user->id)
                ->forPeriod($periodKey)
                ->closed()
                ->first();

            if ($existingClosure) {
                Log::info("Period {$periodKey} already closed for user {$user->id}");
                return $existingClosure;
            }

            // Récupérer les analytics non clôturées
            $analytics = SmsAnalytics::byUser($user->id)
                ->forPeriod($periodKey)
                ->notClosed()
                ->get();

            // Créer ou mettre à jour la clôture
            $closure = PeriodClosure::updateOrCreate(
                ['user_id' => $user->id, 'period_key' => $periodKey],
                [
                    'total_sms' => $analytics->count(),
                    'total_cost' => $analytics->sum('total_cost'),
                    'breakdown_by_subaccount' => $this->breakdownBySubAccount($analytics),
                    'breakdown_by_operator' => $this->breakdownByOperator($analytics),
                    'breakdown_by_type' => $this->breakdownByType($analytics),
                    'status' => 'closed',
                    'closed_at' => now(),
                ]
            );

            // Marquer les analytics comme clôturées
            SmsAnalytics::byUser($user->id)
                ->forPeriod($periodKey)
                ->notClosed()
                ->update(['is_closed' => true]);

            Log::info("Period {$periodKey} closed for user {$user->id}", [
                'total_sms' => $closure->total_sms,
                'total_cost' => $closure->total_cost,
            ]);

            return $closure;
        });
    }

    /**
     * Clôturer une période pour tous les utilisateurs
     */
    public function closeAllUsers(string $periodKey): array
    {
        $results = [];

        User::chunk(100, function ($users) use ($periodKey, &$results) {
            foreach ($users as $user) {
                try {
                    $closure = $this->closePeriod($user, $periodKey);
                    $results[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'status' => 'success',
                        'total_sms' => $closure->total_sms,
                        'total_cost' => $closure->total_cost,
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'status' => 'error',
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Failed to close period for user {$user->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $results;
    }

    /**
     * Générer un rapport de clôture
     */
    public function generateReport(PeriodClosure $closure): array
    {
        return [
            'period' => $closure->period_key,
            'formatted_period' => $closure->formatted_period,
            'user' => [
                'id' => $closure->user->id,
                'name' => $closure->user->name,
                'email' => $closure->user->email,
            ],
            'summary' => [
                'total_sms' => $closure->total_sms,
                'total_cost' => $closure->total_cost,
                'currency' => 'FCFA',
            ],
            'breakdown' => [
                'by_subaccount' => $closure->breakdown_by_subaccount,
                'by_operator' => $closure->breakdown_by_operator,
                'by_type' => $closure->breakdown_by_type,
            ],
            'status' => $closure->status,
            'closed_at' => $closure->closed_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Obtenir l'historique des clôtures
     */
    public function getHistory(int $userId, int $limit = 12): array
    {
        return PeriodClosure::byUser($userId)
            ->orderBy('period_key', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($closure) => $this->generateReport($closure))
            ->toArray();
    }

    /**
     * Ventilation par sous-compte
     */
    private function breakdownBySubAccount($analytics): array
    {
        return $analytics->groupBy('sub_account_id')
            ->map(fn($group) => [
                'count' => $group->count(),
                'cost' => round($group->sum('total_cost'), 2),
                'parts' => $group->sum('sms_parts'),
            ])->toArray();
    }

    /**
     * Ventilation par opérateur
     */
    private function breakdownByOperator($analytics): array
    {
        return $analytics->groupBy('operator')
            ->map(fn($group) => [
                'count' => $group->count(),
                'cost' => round($group->sum('total_cost'), 2),
                'parts' => $group->sum('sms_parts'),
            ])->toArray();
    }

    /**
     * Ventilation par type de message
     */
    private function breakdownByType($analytics): array
    {
        return $analytics->groupBy('message_type')
            ->map(fn($group) => [
                'count' => $group->count(),
                'cost' => round($group->sum('total_cost'), 2),
                'parts' => $group->sum('sms_parts'),
            ])->toArray();
    }
}
