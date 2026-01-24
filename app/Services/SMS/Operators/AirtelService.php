<?php

namespace App\Services\SMS\Operators;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirtelService
{
    protected ?string $apiUrl;
    protected ?string $username;
    protected ?string $password;
    protected ?string $originAddr;

    public function __construct()
    {
        // Charger depuis la base de données en priorité, sinon depuis config
        $dbConfig = \App\Models\SmsConfig::where('provider', 'airtel')->first();

        if ($dbConfig && $dbConfig->is_active) {
            $this->apiUrl = $dbConfig->api_url;
            $this->username = $dbConfig->username;
            $this->password = $dbConfig->password;
            $this->originAddr = $dbConfig->origin_addr;
        } else {
            // Fallback sur les variables d'environnement
            $this->apiUrl = config('sms.airtel.api_url', 'https://messaging.airtel.ga:9002/smshttp/qs/');
            $this->username = config('sms.airtel.username', '');
            $this->password = config('sms.airtel.password', '');
            $this->originAddr = config('sms.airtel.origin_addr', '');
        }
    }

    /**
     * Envoyer un SMS via l'API Airtel
     *
     * @param string $phoneNumber Numéro de téléphone (ex: 24177750737)
     * @param string $message Message à envoyer
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Nettoyer le numéro de téléphone (enlever espaces, +, etc.)
            $cleanNumber = $this->cleanPhoneNumber($phoneNumber);

            Log::info('Airtel SMS - Envoi', [
                'phone' => $cleanNumber,
                'message' => $message,
                'origin' => $this->originAddr
            ]);

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get($this->apiUrl, [
                    'REQUESTTYPE' => 'SMSSubmitReq',
                    'MOBILENO' => $cleanNumber,
                    'USERNAME' => $this->username,
                    'PASSWORD' => $this->password,
                    'ORIGIN_ADDR' => $this->originAddr,
                    'TYPE' => '0',
                    'MESSAGE' => $message,
                ]);

            $body = $response->body();

            // Airtel retourne "+OK|messageId|Success|timestamp" en cas de succès
            if ($response->successful() && str_starts_with($body, '+OK')) {
                Log::info('Airtel SMS - Succès', [
                    'phone' => $cleanNumber,
                    'response' => $body
                ]);

                // Extraire le message ID de la réponse
                $parts = explode('|', $body);
                $messageId = $parts[1] ?? null;

                return [
                    'success' => true,
                    'message' => 'SMS envoyé avec succès',
                    'provider' => 'airtel',
                    'phone' => $cleanNumber,
                    'message_id' => $messageId,
                    'response' => $body,
                ];
            }

            Log::error('Airtel SMS - Échec', [
                'phone' => $cleanNumber,
                'status' => $response->status(),
                'response' => $body
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du SMS',
                'provider' => 'airtel',
                'phone' => $cleanNumber,
                'error' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('Airtel SMS - Exception', [
                'phone' => $phoneNumber,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception lors de l\'envoi du SMS',
                'provider' => 'airtel',
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
            'provider' => 'airtel',
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
     * Note: À adapter selon la documentation Airtel
     *
     * @return array|null
     */
    public function getBalance(): ?array
    {
        // À implémenter si l'API Airtel fournit cette fonctionnalité
        return null;
    }

    /**
     * Vérifier le statut d'un message (si l'API le permet)
     * Note: À adapter selon la documentation Airtel
     *
     * @param string $messageId
     * @return array|null
     */
    public function getMessageStatus(string $messageId): ?array
    {
        // À implémenter si l'API Airtel fournit cette fonctionnalité
        return null;
    }
}
