<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsalaService implements SmsProviderInterface
{
    protected $apiKey;
    protected $senderId;
    protected $baseUrl = 'https://api.smsala.com/api/v1';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->senderId = $config['sender_id'] ?? 'SMSALA';
    }

    /**
     * Envoyer un SMS via SMSALA
     */
    public function send($to, string $message): array
    {
        try {
            $recipients = is_array($to) ? $to : [$to];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send', [
                'from' => $this->senderId,
                'to' => $recipients,
                'text' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message' => 'SMS envoyé avec succès',
                    'provider' => 'smsala',
                    'recipients_count' => count($recipients),
                    'response' => $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de l\'envoi du SMS',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('SMSALA Send Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tester la connexion SMSALA
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/account/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'balance' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de la connexion',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('SMSALA Test Connection Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur de connexion',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le solde SMSALA
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/account/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de récupérer le solde',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('SMSALA Get Balance Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération du solde',
                'error' => $e->getMessage()
            ];
        }
    }
}
