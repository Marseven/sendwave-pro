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

    /**
     * Fallback order: primary provider -> fallback provider
     */
    protected array $fallbackOrder = ['airtel', 'moov'];

    /**
     * Maximum retry attempts per provider
     */
    protected int $maxRetries = 2;

    public function __construct()
    {
        $this->airtelService = new AirtelService();
        $this->moovService = new MoovService();
    }

    /**
     * Check if fallback is enabled
     */
    protected function isFallbackEnabled(): bool
    {
        return config('sms.fallback_enabled', true);
    }

    /**
     * Get fallback provider for a given operator
     */
    protected function getFallbackProvider(string $operator): ?string
    {
        $fallbackMap = [
            'airtel' => 'moov',
            'moov' => 'airtel',
        ];

        return $fallbackMap[$operator] ?? null;
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
     * Send SMS via a specific provider
     */
    protected function sendViaProvider(string $provider, string $phoneNumber, string $message): array
    {
        switch ($provider) {
            case 'airtel':
                return $this->airtelService->sendSms($phoneNumber, $message);
            case 'moov':
                return $this->moovService->sendSms($phoneNumber, $message);
            default:
                return [
                    'success' => false,
                    'message' => 'Provider non supporté',
                    'error_code' => 'UNSUPPORTED_PROVIDER',
                    'provider' => $provider,
                ];
        }
    }

    /**
     * Check if an error is recoverable (should try fallback)
     */
    protected function isRecoverableError(array $result): bool
    {
        if ($result['success']) {
            return false;
        }

        // Errors that should trigger fallback
        $recoverableErrors = [
            'CONNECTION_ERROR',
            'TIMEOUT',
            'SERVICE_UNAVAILABLE',
            'GATEWAY_ERROR',
            'RATE_LIMIT',
            'OPERATOR_DISABLED',
        ];

        $errorCode = $result['error_code'] ?? '';

        return in_array($errorCode, $recoverableErrors) ||
               str_contains($result['message'] ?? '', 'timeout') ||
               str_contains($result['message'] ?? '', 'connection') ||
               str_contains($result['message'] ?? '', 'unavailable');
    }

    /**
     * Envoyer un SMS avec fallback automatique
     *
     * @param string $phoneNumber
     * @param string $message
     * @param bool $allowFallback
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message, bool $allowFallback = true): array
    {
        $operator = OperatorDetector::detect($phoneNumber);
        $info = OperatorDetector::getInfo($phoneNumber);

        Log::info('SMS Router - Envoi', [
            'phone' => $phoneNumber,
            'operator' => $operator,
            'info' => $info,
            'fallback_enabled' => $allowFallback && $this->isFallbackEnabled(),
        ]);

        // Unknown operator - cannot send
        if ($operator === 'unknown') {
            return [
                'success' => false,
                'message' => 'Opérateur non reconnu pour ce numéro. Seuls les numéros Airtel (77, 74, 76) et Moov (60, 62, 65, 66) sont supportés.',
                'error_code' => 'UNKNOWN_OPERATOR',
                'provider' => 'unknown',
                'phone' => $phoneNumber,
                'operator_info' => $info,
                'fallback_attempted' => false,
            ];
        }

        // Try primary operator
        $primaryEnabled = $this->isOperatorEnabled($operator);
        $attempts = [];

        if ($primaryEnabled) {
            $result = $this->sendViaProvider($operator, $phoneNumber, $message);
            $result['phone'] = $phoneNumber;
            $result['operator_info'] = $info;
            $result['provider'] = $operator;

            $attempts[] = [
                'provider' => $operator,
                'success' => $result['success'],
                'message' => $result['message'] ?? null,
            ];

            if ($result['success']) {
                $result['fallback_attempted'] = false;
                $result['attempts'] = $attempts;
                return $result;
            }

            Log::warning('SMS Router - Primary provider failed', [
                'phone' => $phoneNumber,
                'provider' => $operator,
                'error' => $result['message'] ?? 'Unknown error',
            ]);

            // Check if we should try fallback
            if (!$allowFallback || !$this->isFallbackEnabled() || !$this->isRecoverableError($result)) {
                $result['fallback_attempted'] = false;
                $result['attempts'] = $attempts;
                return $result;
            }
        } else {
            $attempts[] = [
                'provider' => $operator,
                'success' => false,
                'message' => 'Operator disabled',
            ];

            Log::info('SMS Router - Primary operator disabled, trying fallback', [
                'phone' => $phoneNumber,
                'primary_operator' => $operator,
            ]);
        }

        // Try fallback provider
        $fallbackProvider = $this->getFallbackProvider($operator);

        if ($fallbackProvider && $this->isOperatorEnabled($fallbackProvider)) {
            Log::info('SMS Router - Attempting fallback', [
                'phone' => $phoneNumber,
                'fallback_provider' => $fallbackProvider,
            ]);

            $fallbackResult = $this->sendViaProvider($fallbackProvider, $phoneNumber, $message);
            $fallbackResult['phone'] = $phoneNumber;
            $fallbackResult['operator_info'] = $info;
            $fallbackResult['original_operator'] = $operator;
            $fallbackResult['provider'] = $fallbackProvider;
            $fallbackResult['fallback_attempted'] = true;
            $fallbackResult['fallback_used'] = $fallbackResult['success'];

            $attempts[] = [
                'provider' => $fallbackProvider,
                'success' => $fallbackResult['success'],
                'message' => $fallbackResult['message'] ?? null,
            ];

            $fallbackResult['attempts'] = $attempts;

            if ($fallbackResult['success']) {
                Log::info('SMS Router - Fallback succeeded', [
                    'phone' => $phoneNumber,
                    'fallback_provider' => $fallbackProvider,
                ]);
            } else {
                Log::error('SMS Router - Fallback also failed', [
                    'phone' => $phoneNumber,
                    'fallback_provider' => $fallbackProvider,
                    'error' => $fallbackResult['message'] ?? 'Unknown error',
                ]);
            }

            return $fallbackResult;
        }

        // No fallback available
        Log::warning('SMS Router - No fallback available', [
            'phone' => $phoneNumber,
            'operator' => $operator,
            'fallback_provider' => $fallbackProvider,
            'fallback_enabled' => $fallbackProvider ? $this->isOperatorEnabled($fallbackProvider) : false,
        ]);

        return [
            'success' => false,
            'message' => $primaryEnabled
                ? 'Échec de l\'envoi et aucun fallback disponible'
                : $this->getDisabledMessage($operator) . ' Aucun fallback disponible.',
            'error_code' => 'NO_FALLBACK_AVAILABLE',
            'provider' => $operator,
            'phone' => $phoneNumber,
            'operator_info' => $info,
            'fallback_attempted' => true,
            'fallback_used' => false,
            'attempts' => $attempts,
        ];
    }

    /**
     * Envoyer des SMS en masse avec routage automatique par opérateur et fallback
     *
     * @param array $phoneNumbers
     * @param string $message
     * @param bool $allowFallback
     * @return array
     */
    public function sendBulkSms(array $phoneNumbers, string $message, bool $allowFallback = true): array
    {
        // Grouper les numéros par opérateur
        $grouped = OperatorDetector::groupByOperator($phoneNumbers);

        $results = [
            'total' => count($phoneNumbers),
            'sent' => 0,
            'failed' => 0,
            'fallback_used' => 0,
            'by_operator' => [],
            'details' => [],
        ];

        // Process Airtel numbers
        if (!empty($grouped['airtel'])) {
            $airtelResults = $this->processBulkForOperator(
                'airtel',
                $grouped['airtel'],
                $message,
                $allowFallback
            );
            $results['sent'] += $airtelResults['sent'];
            $results['failed'] += $airtelResults['failed'];
            $results['fallback_used'] += $airtelResults['fallback_used'];
            $results['by_operator']['airtel'] = $airtelResults;
            $results['details'] = array_merge($results['details'], $airtelResults['results']);
        }

        // Process Moov numbers
        if (!empty($grouped['moov'])) {
            $moovResults = $this->processBulkForOperator(
                'moov',
                $grouped['moov'],
                $message,
                $allowFallback
            );
            $results['sent'] += $moovResults['sent'];
            $results['failed'] += $moovResults['failed'];
            $results['fallback_used'] += $moovResults['fallback_used'];
            $results['by_operator']['moov'] = $moovResults;
            $results['details'] = array_merge($results['details'], $moovResults['results']);
        }

        // Unknown operator numbers
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
                    'fallback_attempted' => false,
                ];
            }
        }

        return $results;
    }

    /**
     * Process bulk SMS for a specific operator with fallback support
     */
    protected function processBulkForOperator(
        string $operator,
        array $phoneNumbers,
        string $message,
        bool $allowFallback
    ): array {
        Log::info('SMS Router - Bulk processing', [
            'operator' => $operator,
            'count' => count($phoneNumbers),
        ]);

        $results = [
            'sent' => 0,
            'failed' => 0,
            'fallback_used' => 0,
            'results' => [],
        ];

        $primaryEnabled = $this->isOperatorEnabled($operator);

        if ($primaryEnabled) {
            // Try bulk send via primary operator
            $service = $operator === 'airtel' ? $this->airtelService : $this->moovService;
            $bulkResult = $service->sendBulkSms($phoneNumbers, $message);

            // Process results and identify failed ones for fallback
            $failedNumbers = [];

            foreach ($bulkResult['results'] as $result) {
                if ($result['success']) {
                    $results['sent']++;
                    $result['fallback_attempted'] = false;
                    $result['fallback_used'] = false;
                    $results['results'][] = $result;
                } else {
                    // Check if should try fallback
                    if ($allowFallback && $this->isFallbackEnabled() && $this->isRecoverableError($result)) {
                        $failedNumbers[] = $result['phone'];
                    } else {
                        $results['failed']++;
                        $result['fallback_attempted'] = false;
                        $results['results'][] = $result;
                    }
                }
            }

            // Process fallback for failed numbers
            if (!empty($failedNumbers)) {
                $fallbackResults = $this->processFallback($operator, $failedNumbers, $message);
                $results['sent'] += $fallbackResults['sent'];
                $results['failed'] += $fallbackResults['failed'];
                $results['fallback_used'] += $fallbackResults['sent'];
                $results['results'] = array_merge($results['results'], $fallbackResults['results']);
            }
        } else {
            // Primary disabled - try fallback for all numbers
            if ($allowFallback && $this->isFallbackEnabled()) {
                $fallbackResults = $this->processFallback($operator, $phoneNumbers, $message);
                $results = $fallbackResults;
            } else {
                // No fallback - mark all as failed
                foreach ($phoneNumbers as $phone) {
                    $results['failed']++;
                    $results['results'][] = [
                        'success' => false,
                        'message' => $this->getDisabledMessage($operator),
                        'error_code' => 'OPERATOR_DISABLED',
                        'provider' => $operator,
                        'phone' => $phone,
                        'fallback_attempted' => false,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Process fallback for failed numbers
     */
    protected function processFallback(string $originalOperator, array $phoneNumbers, string $message): array
    {
        $fallbackProvider = $this->getFallbackProvider($originalOperator);

        $results = [
            'sent' => 0,
            'failed' => 0,
            'results' => [],
        ];

        if (!$fallbackProvider || !$this->isOperatorEnabled($fallbackProvider)) {
            // No fallback available
            foreach ($phoneNumbers as $phone) {
                $results['failed']++;
                $results['results'][] = [
                    'success' => false,
                    'message' => 'Échec de l\'envoi et aucun fallback disponible',
                    'error_code' => 'NO_FALLBACK_AVAILABLE',
                    'provider' => $originalOperator,
                    'phone' => $phone,
                    'fallback_attempted' => true,
                    'fallback_used' => false,
                ];
            }
            return $results;
        }

        Log::info('SMS Router - Processing fallback', [
            'original_operator' => $originalOperator,
            'fallback_provider' => $fallbackProvider,
            'count' => count($phoneNumbers),
        ]);

        $service = $fallbackProvider === 'airtel' ? $this->airtelService : $this->moovService;
        $bulkResult = $service->sendBulkSms($phoneNumbers, $message);

        foreach ($bulkResult['results'] as $result) {
            $result['original_operator'] = $originalOperator;
            $result['fallback_attempted'] = true;
            $result['fallback_used'] = $result['success'];

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            $results['results'][] = $result;
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
            'fallback_enabled' => $this->isFallbackEnabled(),
            'grouped' => $grouped,
        ];
    }

    /**
     * Get router status and configuration
     */
    public function getStatus(): array
    {
        return [
            'airtel' => [
                'enabled' => $this->isOperatorEnabled('airtel'),
                'service' => 'AirtelService',
            ],
            'moov' => [
                'enabled' => $this->isOperatorEnabled('moov'),
                'service' => 'MoovService',
            ],
            'fallback' => [
                'enabled' => $this->isFallbackEnabled(),
                'order' => $this->fallbackOrder,
            ],
        ];
    }
}
