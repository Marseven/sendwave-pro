<?php

namespace App\Services;

use App\Models\Message;
use App\Models\SmsAnalytics;

class AnalyticsRecordService
{
    /**
     * Enregistrer un SMS dans les analytics
     */
    public function recordSms(Message $message, array $context = []): SmsAnalytics
    {
        $smsParts = $this->calculateSmsParts($message->content ?? '');

        return SmsAnalytics::create([
            'user_id' => $message->user_id,
            'sub_account_id' => $context['sub_account_id'] ?? null,
            'campaign_id' => $message->campaign_id,
            'message_id' => $message->id,
            'api_key_id' => $context['api_key_id'] ?? null,
            'country_code' => $this->detectCountry($message->recipient_phone),
            'operator' => $message->provider ?? $context['operator'] ?? null,
            'gateway' => $this->determineGateway($message->provider),
            'message_type' => $context['message_type'] ?? 'transactional',
            'unit_cost' => $this->getUnitCost($message->provider),
            'sms_parts' => $smsParts,
            'total_cost' => $message->cost ?? ($smsParts * $this->getUnitCost($message->provider)),
            'status' => $message->status,
            'period_key' => now()->format('Y-m'),
            'is_closed' => false,
        ]);
    }

    /**
     * Enregistrer plusieurs SMS (bulk)
     */
    public function recordBulkSms(array $messages, array $context = []): int
    {
        $recorded = 0;

        foreach ($messages as $message) {
            if ($message instanceof Message) {
                $this->recordSms($message, $context);
                $recorded++;
            }
        }

        return $recorded;
    }

    /**
     * Détecter le pays via le préfixe du numéro
     */
    private function detectCountry(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $prefixes = [
            '241' => 'GA', // Gabon
            '237' => 'CM', // Cameroun
            '242' => 'CG', // Congo
            '225' => 'CI', // Côte d'Ivoire
            '221' => 'SN', // Sénégal
            '33' => 'FR',  // France
        ];

        foreach ($prefixes as $prefix => $country) {
            if (str_starts_with($phone, $prefix)) {
                return $country;
            }
        }

        return 'GA'; // Par défaut Gabon
    }

    /**
     * Déterminer la passerelle utilisée
     */
    private function determineGateway(?string $provider): string
    {
        return match ($provider) {
            'airtel' => 'airtel_http',
            'moov' => 'moov_smpp',
            default => 'unknown',
        };
    }

    /**
     * Obtenir le coût unitaire par opérateur
     */
    private function getUnitCost(?string $provider): float
    {
        return match ($provider) {
            'airtel' => (float) config('sms.airtel.cost_per_sms', 20),
            'moov' => (float) config('sms.moov.cost_per_sms', 20),
            default => (float) config('sms.cost_per_sms', 20),
        };
    }

    /**
     * Calculer le nombre de parties SMS
     */
    private function calculateSmsParts(string $content): int
    {
        $length = mb_strlen($content);

        if ($length <= 160) {
            return 1;
        }

        // SMS multipart: 153 caractères par partie (7 caractères pour header UDH)
        return (int) ceil($length / 153);
    }

    /**
     * Obtenir les statistiques pour une période
     */
    public function getStatsByPeriod(int $userId, string $period): array
    {
        $analytics = SmsAnalytics::byUser($userId)
            ->forPeriod($period)
            ->get();

        return [
            'total_sms' => $analytics->count(),
            'total_cost' => $analytics->sum('total_cost'),
            'total_parts' => $analytics->sum('sms_parts'),
            'by_status' => [
                'sent' => $analytics->where('status', 'sent')->count(),
                'delivered' => $analytics->where('status', 'delivered')->count(),
                'failed' => $analytics->where('status', 'failed')->count(),
            ],
            'by_operator' => [
                'airtel' => $analytics->where('operator', 'airtel')->count(),
                'moov' => $analytics->where('operator', 'moov')->count(),
            ],
            'by_type' => [
                'transactional' => $analytics->where('message_type', 'transactional')->count(),
                'marketing' => $analytics->where('message_type', 'marketing')->count(),
            ],
        ];
    }
}
