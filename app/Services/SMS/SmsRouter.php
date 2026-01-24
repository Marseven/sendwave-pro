<?php

namespace App\Services\SMS;

use App\Models\SmsConfig;
use App\Services\SMS\Operators\AirtelService;
use App\Services\SMS\Operators\MoovService;
use Illuminate\Support\Facades\Log;

class SmsRouter
{
    protected AirtelService $airtelService;
    protected MoovService $moovService;

    public function __construct()
    {
        $this->airtelService = new AirtelService();
        $this->moovService = new MoovService();
    }

    /**
     * Vérifier si un opérateur est activé (DB ou .env)
     */
    protected function isOperatorEnabled(string $operator): bool
    {
        $dbConfig = SmsConfig::where('provider', $operator)->first();

        if ($dbConfig) {
            return $dbConfig->is_active;
        }

        return config("sms.{$operator}.enabled", false);
    }

    /**
     * Obtenir le message d'erreur pour un opérateur désactivé
     */
    protected function getDisabledMessage(string $operator): string
    {
        $names = [
            'airtel' => 'Airtel',
            'moov' => 'Moov',
        ];

        $name = $names[$operator] ?? ucfirst($operator);
        return "L'opérateur {$name} est actuellement désactivé. Veuillez contacter l'administrateur ou activer l'opérateur dans la configuration SMS.";
    }

    /**
     * Envoyer un SMS en détectant automatiquement l'opérateur
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        $operator = OperatorDetector::detect($phoneNumber);
        $info = OperatorDetector::getInfo($phoneNumber);

        Log::info('SMS Router - Envoi', [
            'phone' => $phoneNumber,
            'operator' => $operator,
            'info' => $info
        ]);

        switch ($operator) {
            case 'airtel':
                if (!$this->isOperatorEnabled('airtel')) {
                    return [
                        'success' => false,
                        'message' => $this->getDisabledMessage('airtel'),
                        'error_code' => 'OPERATOR_DISABLED',
                        'provider' => 'airtel',
                        'phone' => $phoneNumber,
                        'operator_info' => $info,
                    ];
                }
                return $this->airtelService->sendSms($phoneNumber, $message);

            case 'moov':
                if (!$this->isOperatorEnabled('moov')) {
                    return [
                        'success' => false,
                        'message' => $this->getDisabledMessage('moov'),
                        'error_code' => 'OPERATOR_DISABLED',
                        'provider' => 'moov',
                        'phone' => $phoneNumber,
                        'operator_info' => $info,
                    ];
                }
                return $this->moovService->sendSms($phoneNumber, $message);

            default:
                return [
                    'success' => false,
                    'message' => 'Opérateur non reconnu pour ce numéro. Seuls les numéros Airtel (77, 74, 76) et Moov (60, 62, 65, 66) sont supportés.',
                    'error_code' => 'UNKNOWN_OPERATOR',
                    'provider' => 'unknown',
                    'phone' => $phoneNumber,
                    'operator_info' => $info,
                ];
        }
    }

    /**
     * Envoyer des SMS en masse avec routage automatique par opérateur
     *
     * @param array $phoneNumbers
     * @param string $message
     * @return array
     */
    public function sendBulkSms(array $phoneNumbers, string $message): array
    {
        // Grouper les numéros par opérateur
        $grouped = OperatorDetector::groupByOperator($phoneNumbers);

        $results = [
            'total' => count($phoneNumbers),
            'sent' => 0,
            'failed' => 0,
            'by_operator' => [],
            'details' => [],
        ];

        // Envoyer via Airtel
        if (!empty($grouped['airtel'])) {
            Log::info('SMS Router - Bulk Airtel', [
                'count' => count($grouped['airtel'])
            ]);

            if ($this->isOperatorEnabled('airtel')) {
                $airtelResults = $this->airtelService->sendBulkSms($grouped['airtel'], $message);
                $results['sent'] += $airtelResults['sent'];
                $results['failed'] += $airtelResults['failed'];
                $results['by_operator']['airtel'] = $airtelResults;
                $results['details'] = array_merge($results['details'], $airtelResults['results']);
            } else {
                foreach ($grouped['airtel'] as $phone) {
                    $results['failed']++;
                    $results['details'][] = [
                        'success' => false,
                        'message' => $this->getDisabledMessage('airtel'),
                        'error_code' => 'OPERATOR_DISABLED',
                        'provider' => 'airtel',
                        'phone' => $phone,
                    ];
                }
            }
        }

        // Envoyer via Moov
        if (!empty($grouped['moov'])) {
            Log::info('SMS Router - Bulk Moov', [
                'count' => count($grouped['moov'])
            ]);

            if ($this->isOperatorEnabled('moov')) {
                $moovResults = $this->moovService->sendBulkSms($grouped['moov'], $message);
                $results['sent'] += $moovResults['sent'];
                $results['failed'] += $moovResults['failed'];
                $results['by_operator']['moov'] = $moovResults;
                $results['details'] = array_merge($results['details'], $moovResults['results']);
            } else {
                foreach ($grouped['moov'] as $phone) {
                    $results['failed']++;
                    $results['details'][] = [
                        'success' => false,
                        'message' => $this->getDisabledMessage('moov'),
                        'error_code' => 'OPERATOR_DISABLED',
                        'provider' => 'moov',
                        'phone' => $phone,
                    ];
                }
            }
        }

        // Numéros avec opérateur inconnu
        if (!empty($grouped['unknown'])) {
            Log::warning('SMS Router - Numéros inconnus', [
                'count' => count($grouped['unknown']),
                'numbers' => $grouped['unknown']
            ]);

            foreach ($grouped['unknown'] as $phone) {
                $results['failed']++;
                $results['details'][] = [
                    'success' => false,
                    'message' => 'Opérateur non reconnu pour ce numéro',
                    'error_code' => 'UNKNOWN_OPERATOR',
                    'provider' => 'unknown',
                    'phone' => $phone,
                ];
            }
        }

        return $results;
    }

    /**
     * Obtenir des statistiques sur les numéros
     *
     * @param array $phoneNumbers
     * @return array
     */
    public function analyzeNumbers(array $phoneNumbers): array
    {
        $grouped = OperatorDetector::groupByOperator($phoneNumbers);

        return [
            'total' => count($phoneNumbers),
            'airtel_count' => count($grouped['airtel']),
            'moov_count' => count($grouped['moov']),
            'unknown_count' => count($grouped['unknown']),
            'airtel_enabled' => $this->isOperatorEnabled('airtel'),
            'moov_enabled' => $this->isOperatorEnabled('moov'),
            'grouped' => $grouped,
        ];
    }
}
