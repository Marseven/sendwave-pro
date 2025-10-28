<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhapiService implements SmsProviderInterface
{
    protected $apiKey;
    protected $channelId;
    protected $baseUrl = 'https://gate.whapi.cloud';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->channelId = $config['config']['channel_id'] ?? null;
    }

    /**
     * Envoyer un message via WHAPI (WhatsApp)
     */
    public function send($to, string $message): array
    {
        try {
            $recipients = is_array($to) ? $to : [$to];
            $results = [];

            foreach ($recipients as $number) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post($this->baseUrl . '/messages/text', [
                    'to' => $this->formatPhoneNumber($number),
                    'body' => $message,
                ]);

                if ($response->successful()) {
                    $results[] = $response->json();
                } else {
                    Log::warning('WHAPI Send Warning for ' . $number . ': ' . $response->body());
                }
            }

            if (count($results) > 0) {
                return [
                    'success' => true,
                    'message' => 'Messages envoyés avec succès',
                    'provider' => 'whapi',
                    'recipients_count' => count($results),
                    'response' => $results
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de l\'envoi des messages',
            ];

        } catch (\Exception $e) {
            Log::error('WHAPI Send Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tester la connexion WHAPI
     */
    public function testConnection(): array
    {
        try {
            // Test avec un endpoint simple pour vérifier l'authentification
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/settings');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'status' => $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de la connexion',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('WHAPI Test Connection Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur de connexion',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les informations du compte WHAPI
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . '/settings');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'account' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de récupérer les informations',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('WHAPI Get Account Info Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formater le numéro de téléphone pour WhatsApp (WHAPI format)
     */
    protected function formatPhoneNumber(string $number): string
    {
        // Supprimer tous les caractères non numériques sauf le +
        $number = preg_replace('/[^0-9+]/', '', $number);

        // Si le numéro commence par +, le garder tel quel
        // Sinon, ajouter le +
        if (!str_starts_with($number, '+')) {
            $number = '+' . $number;
        }

        // WHAPI accepte les numéros au format international avec ou sans @c.us
        // On retourne le format simple: +1234567890
        return $number;
    }
}
