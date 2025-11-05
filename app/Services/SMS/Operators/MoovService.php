<?php

namespace App\Services\SMS\Operators;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoovService
{
    protected ?string $apiUrl;
    protected ?string $username;
    protected ?string $password;
    protected ?string $originAddr;

    public function __construct()
    {
        // Charger depuis la base de données en priorité, sinon depuis config
        $dbConfig = \App\Models\SmsConfig::where('provider', 'moov')->first();

        if ($dbConfig && $dbConfig->is_active) {
            $this->apiUrl = $dbConfig->api_url;
            $this->username = $dbConfig->username;
            $this->password = $dbConfig->password;
            $this->originAddr = $dbConfig->origin_addr;
        } else {
            // Fallback sur les variables d'environnement
            $this->apiUrl = config('sms.moov.api_url', '');
            $this->username = config('sms.moov.username', '');
            $this->password = config('sms.moov.password', '');
            $this->originAddr = config('sms.moov.origin_addr', '');
        }
    }

    /**
     * Envoyer un SMS via l'API Moov
     * À implémenter une fois l'API Moov disponible
     *
     * @param string $phoneNumber Numéro de téléphone (ex: 24162345678)
     * @param string $message Message à envoyer
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Nettoyer le numéro de téléphone
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

            Log::info('Moov SMS - Envoi (API non configurée)', [
                'phone' => $cleanNumber,
                'message' => $message,
                'origin' => $this->originAddr
            ]);

            // TODO: Implémenter l'appel API Moov quand disponible
            // Pour le moment, retourner une erreur indiquant que l'API n'est pas configurée

            return [
                'success' => false,
                'message' => 'API Moov non configurée pour le moment',
                'provider' => 'moov',
                'phone' => $cleanNumber,
                'error' => 'API endpoint not configured',
            ];

            /* Exemple d'implémentation future :
            $response = Http::post($this->apiUrl, [
                'username' => $this->username,
                'password' => $this->password,
                'from' => $this->originAddr,
                'to' => $cleanNumber,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'SMS envoyé avec succès',
                    'provider' => 'moov',
                    'phone' => $cleanNumber,
                    'response' => $response->body(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du SMS',
                'provider' => 'moov',
                'phone' => $cleanNumber,
                'error' => $response->body(),
            ];
            */

        } catch (\Exception $e) {
            Log::error('Moov SMS - Exception', [
                'phone' => $phoneNumber,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception lors de l\'envoi du SMS',
                'provider' => 'moov',
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer des SMS en masse
     *
     * @param array $phoneNumbers Tableau de numéros de téléphone
     * @param string $message Message à envoyer
     * @return array
     */
    public function sendBulkSms(array $phoneNumbers, string $message): array
    {
        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($phoneNumbers as $phoneNumber) {
            $result = $this->sendSms($phoneNumber, $message);

            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }

            $results[] = $result;
        }

        return [
            'success' => true,
            'provider' => 'moov',
            'total' => count($phoneNumbers),
            'sent' => $success,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Nettoyer le numéro de téléphone
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function cleanPhoneNumber(string $phoneNumber): string
    {
        // Enlever tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Si le numéro commence par +241, enlever le +
        if (str_starts_with($phoneNumber, '+241')) {
            $cleaned = substr($cleaned, 0);
        }

        return $cleaned;
    }

    /**
     * Vérifier le solde (si l'API le permet)
     *
     * @return array|null
     */
    public function getBalance(): ?array
    {
        return null;
    }

    /**
     * Vérifier le statut d'un message (si l'API le permet)
     *
     * @param string $messageId
     * @return array|null
     */
    public function getMessageStatus(string $messageId): ?array
    {
        return null;
    }
}
